<?php
/**
 * REST endpoints for Toolbox admin actions and future clients.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class Rest_Controller {
	private Settings $settings;
	private OpenAI_Client $client;

	public function __construct( Settings $settings, OpenAI_Client $client ) {
		$this->settings = $settings;
		$this->client   = $client;
	}

	public function register_routes(): void {
		$this->post( '/web-research', 'web_research' );
		$this->post( '/image-candidates', 'image_candidates' );
		$this->post( '/knowledge-search', 'knowledge_search' );
		$this->post( '/flows/article-brief', 'article_brief' );
		$this->post( '/flows/media-brief', 'media_brief' );

		register_rest_route(
			Plugin::REST_NAMESPACE,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => array( $this, 'permission' ),
			)
		);
	}

	public function permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function status(): WP_REST_Response {
		return rest_ensure_response(
			array(
				'provider'                 => 'openai',
				'has_api_key'              => $this->settings->has_api_key(),
				'web_research_enabled'     => (bool) $this->settings->get( 'enable_web_research' ),
				'image_generation_enabled' => (bool) $this->settings->get( 'enable_image_generation' ),
				'knowledge_search_enabled' => (bool) $this->settings->get( 'enable_knowledge_search' ),
				'vector_store_configured'  => '' !== trim( (string) $this->settings->get( 'openai_vector_store_id' ) ),
				'boundary'                 => 'Toolbox returns research, image, and knowledge suggestions only. WordPress writes should be handed to Abilities/Core governance.',
			)
		);
	}

	public function web_research( WP_REST_Request $request ) {
		if ( ! $this->settings->get( 'enable_web_research' ) ) {
			return $this->disabled_error( 'web research' );
		}

		$query = $this->required_text( $request, 'query' );
		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$domains = $this->csv_list( (string) $request->get_param( 'domains' ) );
		return rest_ensure_response( $this->client->web_research( $query, $domains ) );
	}

	public function image_candidates( WP_REST_Request $request ) {
		if ( ! $this->settings->get( 'enable_image_generation' ) ) {
			return $this->disabled_error( 'image generation' );
		}

		$prompt = $this->required_text( $request, 'prompt' );
		if ( is_wp_error( $prompt ) ) {
			return $prompt;
		}

		return rest_ensure_response(
			$this->client->generate_image_candidate(
				$prompt,
				sanitize_text_field( (string) ( $request->get_param( 'size' ) ?: '1024x1024' ) ),
				sanitize_text_field( (string) ( $request->get_param( 'quality' ) ?: 'auto' ) )
			)
		);
	}

	public function knowledge_search( WP_REST_Request $request ) {
		if ( ! $this->settings->get( 'enable_knowledge_search' ) ) {
			return $this->disabled_error( 'knowledge search' );
		}

		$query = $this->required_text( $request, 'query' );
		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$max_results = max( 1, min( 10, (int) ( $request->get_param( 'max_results' ) ?: 4 ) ) );
		return rest_ensure_response( $this->client->knowledge_search( $query, $max_results ) );
	}

	public function article_brief( WP_REST_Request $request ) {
		$topic = $this->required_text( $request, 'topic' );
		if ( is_wp_error( $topic ) ) {
			return $topic;
		}

		return rest_ensure_response( $this->client->build_article_brief( $topic, ! empty( $request->get_param( 'include_knowledge' ) ) ) );
	}

	public function media_brief( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		if ( 0 === $post_id ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_post_id',
				__( 'A post_id is required for the media brief flow.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'magick_ai_toolbox_post_not_found',
				__( 'The requested post was not found.', 'magick-ai-toolbox' ),
				array( 'status' => 404 )
			);
		}

		$context = wp_json_encode(
			array(
				'id'      => $post_id,
				'title'   => get_the_title( $post ),
				'type'    => get_post_type( $post ),
				'status'  => get_post_status( $post ),
				'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'content' => wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 350 ),
			),
			JSON_PRETTY_PRINT
		);

		return rest_ensure_response( $this->client->build_media_brief( (string) $context ) );
	}

	private function post( string $route, string $method ): void {
		register_rest_route(
			Plugin::REST_NAMESPACE,
			$route,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, $method ),
				'permission_callback' => array( $this, 'permission' ),
			)
		);
	}

	private function required_text( WP_REST_Request $request, string $key ) {
		$value = trim( sanitize_textarea_field( (string) $request->get_param( $key ) ) );
		if ( '' === $value ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_' . sanitize_key( $key ),
				sprintf(
					/* translators: %s: field name. */
					__( '%s is required.', 'magick-ai-toolbox' ),
					$key
				),
				array( 'status' => 400 )
			);
		}

		return $value;
	}

	private function csv_list( string $value ): array {
		$items = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $items ),
				static fn( string $item ): bool => '' !== $item
			)
		);
	}

	private function disabled_error( string $label ): WP_Error {
		return new WP_Error(
			'magick_ai_toolbox_disabled',
			sprintf(
				/* translators: %s: feature label. */
				__( 'Enable %s in Magick AI Toolbox settings before running this tool.', 'magick-ai-toolbox' ),
				$label
			),
			array( 'status' => 403 )
		);
	}
}
