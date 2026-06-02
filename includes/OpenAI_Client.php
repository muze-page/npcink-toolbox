<?php
/**
 * Minimal OpenAI HTTP client for Toolbox provider actions.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class OpenAI_Client {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function web_research( string $query, array $domains = array() ) {
		$tool = array(
			'type'                => 'web_search',
			'external_web_access' => (bool) $this->settings->get( 'external_web_access' ),
		);

		if ( ! empty( $domains ) ) {
			$tool['filters'] = array(
				'type'    => 'allowed_domains',
				'domains' => array_values( $domains ),
			);
		}

		$response = $this->responses(
			array(
				'model'       => (string) $this->settings->get( 'openai_model' ),
				'tools'       => array( $tool ),
				'tool_choice' => 'auto',
				'input'       => "Research the following WordPress operator request. Return a concise answer, practical next steps, and cite sources when available.\n\nRequest: " . $query,
			)
		);

		return $this->normalize_text_response( $response );
	}

	public function knowledge_search( string $query, int $max_results = 4 ) {
		$vector_store_id = trim( (string) $this->settings->get( 'openai_vector_store_id' ) );
		if ( '' === $vector_store_id ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_vector_store',
				__( 'Configure an OpenAI vector store id before running knowledge search.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		}

		$response = $this->responses(
			array(
				'model'   => (string) $this->settings->get( 'openai_model' ),
				'input'   => "Search the configured site knowledge base and answer with practical WordPress operator context.\n\nQuery: " . $query,
				'tools'   => array(
					array(
						'type'             => 'file_search',
						'vector_store_ids' => array( $vector_store_id ),
						'max_num_results'  => max( 1, min( 10, $max_results ) ),
					),
				),
				'include' => array( 'file_search_call.results' ),
			)
		);

		return $this->normalize_text_response( $response );
	}

	public function generate_image_candidate( string $prompt, string $size = '1024x1024', string $quality = 'auto' ) {
		$body = array(
			'model'   => (string) $this->settings->get( 'openai_image_model' ),
			'prompt'  => $prompt,
			'size'    => $this->sanitize_image_size( $size ),
			'quality' => $this->sanitize_image_quality( $quality ),
			'n'       => 1,
		);

		$response = $this->request( '/images/generations', $body );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$images = array();
		foreach ( (array) ( $response['data'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$images[] = array(
				'b64_json'      => isset( $item['b64_json'] ) ? (string) $item['b64_json'] : '',
				'revised_prompt' => isset( $item['revised_prompt'] ) ? (string) $item['revised_prompt'] : '',
			);
		}

		return array(
			'provider' => 'openai',
			'model'    => (string) $this->settings->get( 'openai_image_model' ),
			'images'   => $images,
			'raw'      => $response,
		);
	}

	public function build_article_brief( string $topic, bool $include_knowledge = true ) {
		$tools = array(
			array(
				'type'                => 'web_search',
				'external_web_access' => (bool) $this->settings->get( 'external_web_access' ),
			),
		);

		$vector_store_id = trim( (string) $this->settings->get( 'openai_vector_store_id' ) );
		if ( $include_knowledge && '' !== $vector_store_id ) {
			$tools[] = array(
				'type'             => 'file_search',
				'vector_store_ids' => array( $vector_store_id ),
				'max_num_results'  => 4,
			);
		}

		$response = $this->responses(
			array(
				'model'       => (string) $this->settings->get( 'openai_model' ),
				'tools'       => $tools,
				'tool_choice' => 'auto',
				'input'       => "Create a WordPress article planning brief for this topic: {$topic}\n\nReturn: search findings, suggested headline, outline, source notes, image prompt, internal-link opportunities, and Core-governed write handoff suggestions. Do not draft final article copy.",
			)
		);

		return $this->normalize_text_response( $response );
	}

	public function build_media_brief( string $post_context ) {
		$response = $this->responses(
			array(
				'model' => (string) $this->settings->get( 'openai_model' ),
				'input' => "Given this WordPress post context, produce a media planning brief. Return image prompt candidates, alt text direction, caption notes, and suggested Core-governed media actions. Do not upload or modify WordPress media.\n\nContext:\n" . $post_context,
			)
		);

		return $this->normalize_text_response( $response );
	}

	private function responses( array $body ) {
		return $this->request( '/responses', $body );
	}

	private function request( string $path, array $body ) {
		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_api_key',
				__( 'Configure an OpenAI API key before running Toolbox provider actions.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1' . $path,
			array(
				'timeout' => 90,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = (string) wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'magick_ai_toolbox_provider_invalid_json',
				__( 'The provider returned an invalid JSON response.', 'magick-ai-toolbox' ),
				array( 'status' => 502 )
			);
		}

		if ( 200 > $status || 299 < $status ) {
			$message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : __( 'The provider request failed.', 'magick-ai-toolbox' );
			return new WP_Error(
				'magick_ai_toolbox_provider_error',
				$message,
				array(
					'status'        => $status,
					'provider_body' => $data,
				)
			);
		}

		return $data;
	}

	private function normalize_text_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'provider'    => 'openai',
			'model'       => (string) ( $response['model'] ?? $this->settings->get( 'openai_model' ) ),
			'text'        => $this->extract_output_text( $response ),
			'annotations' => $this->extract_annotations( $response ),
			'sources'     => is_array( $response['sources'] ?? null ) ? $response['sources'] : array(),
			'raw'         => $response,
		);
	}

	private function extract_output_text( array $response ): string {
		if ( isset( $response['output_text'] ) && is_string( $response['output_text'] ) ) {
			return $response['output_text'];
		}

		$parts = array();
		foreach ( (array) ( $response['output'] ?? array() ) as $output_item ) {
			foreach ( (array) ( is_array( $output_item ) ? ( $output_item['content'] ?? array() ) : array() ) as $content_item ) {
				if ( is_array( $content_item ) && isset( $content_item['text'] ) && is_string( $content_item['text'] ) ) {
					$parts[] = $content_item['text'];
				}
			}
		}

		return trim( implode( "\n\n", $parts ) );
	}

	private function extract_annotations( array $response ): array {
		$annotations = array();
		foreach ( (array) ( $response['output'] ?? array() ) as $output_item ) {
			foreach ( (array) ( is_array( $output_item ) ? ( $output_item['content'] ?? array() ) : array() ) as $content_item ) {
				foreach ( (array) ( is_array( $content_item ) ? ( $content_item['annotations'] ?? array() ) : array() ) as $annotation ) {
					if ( is_array( $annotation ) ) {
						$annotations[] = $annotation;
					}
				}
			}
		}

		return $annotations;
	}

	private function sanitize_image_size( string $size ): string {
		$allowed = array( '1024x1024', '1024x1536', '1536x1024', 'auto' );
		return in_array( $size, $allowed, true ) ? $size : '1024x1024';
	}

	private function sanitize_image_quality( string $quality ): string {
		$allowed = array( 'auto', 'low', 'medium', 'high' );
		return in_array( $quality, $allowed, true ) ? $quality : 'auto';
	}
}
