<?php
/**
 * Settings storage and sanitization.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public function register(): void {
		register_setting(
			'magick_ai_toolbox',
			Plugin::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	public function defaults(): array {
		return array(
			'provider'                => 'openai',
			'openai_api_key'          => '',
			'openai_model'            => 'gpt-4.1',
			'openai_image_model'      => 'gpt-image-1.5',
			'openai_vector_store_id'  => '',
			'enable_web_research'     => true,
			'enable_image_generation' => true,
			'enable_knowledge_search' => true,
			'external_web_access'     => true,
		);
	}

	public function get_all(): array {
		$value = get_option( Plugin::OPTION_NAME, array() );
		return array_merge( $this->defaults(), is_array( $value ) ? $value : array() );
	}

	public function get( string $key ) {
		$settings = $this->get_all();
		return $settings[ $key ] ?? null;
	}

	public function get_api_key(): string {
		if ( defined( 'MAGICK_AI_TOOLBOX_OPENAI_API_KEY' ) && '' !== (string) MAGICK_AI_TOOLBOX_OPENAI_API_KEY ) {
			return (string) MAGICK_AI_TOOLBOX_OPENAI_API_KEY;
		}

		$env_key = getenv( 'OPENAI_API_KEY' );
		if ( is_string( $env_key ) && '' !== $env_key ) {
			return $env_key;
		}

		return (string) $this->get( 'openai_api_key' );
	}

	public function has_api_key(): bool {
		return '' !== $this->get_api_key();
	}

	public function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$current  = $this->get_all();
		$defaults = $this->defaults();

		$clear_key = ! empty( $input['clear_openai_api_key'] );
		$new_key   = isset( $input['openai_api_key'] ) ? trim( (string) $input['openai_api_key'] ) : '';

		$sanitized = array(
			'provider'                => 'openai',
			'openai_api_key'          => $clear_key ? '' : ( '' !== $new_key ? $new_key : (string) $current['openai_api_key'] ),
			'openai_model'            => $this->sanitize_model( $input['openai_model'] ?? $defaults['openai_model'] ),
			'openai_image_model'      => $this->sanitize_model( $input['openai_image_model'] ?? $defaults['openai_image_model'] ),
			'openai_vector_store_id'  => sanitize_text_field( (string) ( $input['openai_vector_store_id'] ?? '' ) ),
			'enable_web_research'     => ! empty( $input['enable_web_research'] ),
			'enable_image_generation' => ! empty( $input['enable_image_generation'] ),
			'enable_knowledge_search' => ! empty( $input['enable_knowledge_search'] ),
			'external_web_access'     => ! empty( $input['external_web_access'] ),
		);

		return $sanitized;
	}

	private function sanitize_model( $value ): string {
		$value = preg_replace( '/[^A-Za-z0-9._:-]/', '', (string) $value );
		return '' !== $value ? $value : 'gpt-4.1';
	}
}
