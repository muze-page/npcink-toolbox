<?php
/**
 * Abilities API registrations for Toolbox actions.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

defined( 'ABSPATH' ) || exit;

final class Abilities {
	private Settings $settings;
	private OpenAI_Client $client;
	private bool $registered_with_helpers = false;

	public function __construct( Settings $settings, OpenAI_Client $client ) {
		$this->settings = $settings;
		$this->client   = $client;
	}

	public function register_with_magick_ai_abilities(): void {
		if ( $this->registered_with_helpers || ! function_exists( 'magick_ai_abilities_register_readonly' ) ) {
			return;
		}

		if ( function_exists( 'magick_ai_abilities_register_category' ) ) {
			magick_ai_abilities_register_category(
				'magick-ai-toolbox',
				array(
					'label'       => __( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
					'description' => __( 'External research, image, knowledge, and fixed-flow tools.', 'magick-ai-toolbox' ),
				)
			);
		}

		foreach ( $this->definitions() as $ability_id => $definition ) {
			magick_ai_abilities_register_readonly( $ability_id, $definition );
		}

		$this->registered_with_helpers = true;
	}

	public function register_native_category(): void {
		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				'magick-ai-toolbox',
				array(
					'label'       => __( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
					'description' => __( 'External research, image, knowledge, and fixed-flow tools.', 'magick-ai-toolbox' ),
				)
			);
		}
	}

	public function register_native_abilities(): void {
		if ( $this->registered_with_helpers || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $this->definitions() as $ability_id => $definition ) {
			wp_register_ability(
				$ability_id,
				array(
					'label'               => $definition['label'],
					'description'         => $definition['description'],
					'category'            => 'magick-ai-toolbox',
					'input_schema'        => $definition['input_schema'],
					'output_schema'       => $definition['output_schema'],
					'execute_callback'    => $definition['execute_callback'],
					'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
					'meta'                => array(
						'show_in_rest' => true,
						'readonly'     => true,
					),
				)
			);
		}
	}

	private function definitions(): array {
		return array(
			'magick-ai-toolbox/web-research'              => $this->definition( __( 'Web Research', 'magick-ai-toolbox' ), __( 'Research a topic using the configured external search provider.', 'magick-ai-toolbox' ), array( 'query' ), array( $this, 'web_research' ) ),
			'magick-ai-toolbox/generate-image-candidate'  => $this->definition( __( 'Generate Image Candidate', 'magick-ai-toolbox' ), __( 'Generate one image candidate and return a base64 payload.', 'magick-ai-toolbox' ), array( 'prompt' ), array( $this, 'generate_image_candidate' ) ),
			'magick-ai-toolbox/knowledge-search'          => $this->definition( __( 'Knowledge Search', 'magick-ai-toolbox' ), __( 'Search the configured vector store and return a grounded answer.', 'magick-ai-toolbox' ), array( 'query' ), array( $this, 'knowledge_search' ) ),
			'magick-ai-toolbox/build-article-brief'       => $this->definition( __( 'Build Article Brief', 'magick-ai-toolbox' ), __( 'Build a research-backed article planning brief without writing WordPress content.', 'magick-ai-toolbox' ), array( 'topic' ), array( $this, 'build_article_brief' ) ),
			'magick-ai-toolbox/build-media-brief'         => $this->definition( __( 'Build Media Brief', 'magick-ai-toolbox' ), __( 'Build image prompt and media SEO suggestions from supplied post context.', 'magick-ai-toolbox' ), array( 'post_context' ), array( $this, 'build_media_brief' ) ),
		);
	}

	private function definition( string $label, string $description, array $required, callable $callback ): array {
		$properties = array();
		foreach ( $required as $key ) {
			$properties[ $key ] = array(
				'type' => 'string',
			);
		}

		return array(
			'label'               => $label,
			'description'         => $description,
			'category'            => 'magick-ai-toolbox',
			'capability'          => 'manage_options',
			'required_scope'      => 'cap.toolbox.read',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => $properties,
				'required'             => $required,
				'additionalProperties' => true,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'additionalProperties' => true,
			),
			'execute_callback'    => $callback,
			'project_to_magick_catalog' => true,
		);
	}

	public function web_research( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		return $this->client->web_research( sanitize_textarea_field( (string) ( $input['query'] ?? '' ) ) );
	}

	public function generate_image_candidate( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		return $this->client->generate_image_candidate(
			sanitize_textarea_field( (string) ( $input['prompt'] ?? '' ) ),
			sanitize_text_field( (string) ( $input['size'] ?? '1024x1024' ) ),
			sanitize_text_field( (string) ( $input['quality'] ?? 'auto' ) )
		);
	}

	public function knowledge_search( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		return $this->client->knowledge_search( sanitize_textarea_field( (string) ( $input['query'] ?? '' ) ), (int) ( $input['max_results'] ?? 4 ) );
	}

	public function build_article_brief( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		return $this->client->build_article_brief( sanitize_textarea_field( (string) ( $input['topic'] ?? '' ) ) );
	}

	public function build_media_brief( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		return $this->client->build_media_brief( sanitize_textarea_field( (string) ( $input['post_context'] ?? '' ) ) );
	}
}
