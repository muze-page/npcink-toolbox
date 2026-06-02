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
			'search_provider'       => 'tavily',
			'tavily_api_key'        => '',
			'tavily_search_depth'   => 'basic',
			'tavily_include_answer' => true,
			'tavily_include_raw'    => false,
			'tavily_include_images' => false,
			'image_provider'        => 'unsplash',
			'unsplash_access_key'   => '',
			'unsplash_utm_source'   => 'magick_ai_toolbox',
			'vector_provider'       => 'qdrant',
			'qdrant_endpoint'       => '',
			'qdrant_api_key'        => '',
			'qdrant_collection'     => '',
			'qdrant_vector_name'    => '',
			'embedding_provider'    => 'siliconflow',
			'embedding_dimensions'  => 1024,
			'siliconflow_api_key'   => '',
			'siliconflow_base_url'  => 'https://api.siliconflow.com/v1',
			'siliconflow_model'     => 'BAAI/bge-m3',
			'jina_api_key'          => '',
			'jina_base_url'         => 'https://api.jina.ai/v1',
			'jina_model'            => 'jina-embeddings-v3',
			'include_raw_responses' => false,
			'enable_web_research'   => true,
			'enable_image_source'   => true,
			'enable_vector_search'  => true,
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

	public function get_tavily_api_key(): string {
		return $this->get_secret( 'tavily_api_key', 'MAGICK_AI_TOOLBOX_TAVILY_API_KEY', 'TAVILY_API_KEY' );
	}

	public function has_tavily_api_key(): bool {
		return '' !== $this->get_tavily_api_key();
	}

	public function get_unsplash_access_key(): string {
		return $this->get_secret( 'unsplash_access_key', 'MAGICK_AI_TOOLBOX_UNSPLASH_ACCESS_KEY', 'UNSPLASH_ACCESS_KEY' );
	}

	public function has_unsplash_access_key(): bool {
		return '' !== $this->get_unsplash_access_key();
	}

	public function get_qdrant_api_key(): string {
		return $this->get_secret( 'qdrant_api_key', 'MAGICK_AI_TOOLBOX_QDRANT_API_KEY', 'QDRANT_API_KEY' );
	}

	public function has_qdrant_connection(): bool {
		return '' !== trim( (string) $this->get( 'qdrant_endpoint' ) )
			&& '' !== trim( (string) $this->get( 'qdrant_collection' ) );
	}

	public function get_siliconflow_api_key(): string {
		return $this->get_secret( 'siliconflow_api_key', 'MAGICK_AI_TOOLBOX_SILICONFLOW_API_KEY', 'SILICONFLOW_API_KEY' );
	}

	public function has_siliconflow_api_key(): bool {
		return '' !== $this->get_siliconflow_api_key();
	}

	public function get_jina_api_key(): string {
		return $this->get_secret( 'jina_api_key', 'MAGICK_AI_TOOLBOX_JINA_API_KEY', 'JINA_API_KEY' );
	}

	public function has_jina_api_key(): bool {
		return '' !== $this->get_jina_api_key();
	}

	public function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$current  = $this->get_all();
		$defaults = $this->defaults();

		$clear_tavily_key      = ! empty( $input['clear_tavily_api_key'] );
		$clear_unsplash_key    = ! empty( $input['clear_unsplash_access_key'] );
		$clear_qdrant_key      = ! empty( $input['clear_qdrant_api_key'] );
		$clear_siliconflow_key = ! empty( $input['clear_siliconflow_api_key'] );
		$clear_jina_key        = ! empty( $input['clear_jina_api_key'] );
		$embedding_provider    = in_array( (string) ( $input['embedding_provider'] ?? '' ), array( 'siliconflow', 'jina' ), true ) ? (string) $input['embedding_provider'] : 'siliconflow';
		$embedding_dimensions  = isset( $input['embedding_dimensions'] ) ? absint( $input['embedding_dimensions'] ) : (int) $defaults['embedding_dimensions'];

		$sanitized = array(
			'search_provider'       => 'tavily',
			'tavily_api_key'        => $this->sanitize_secret_input( $input, $current, 'tavily_api_key', $clear_tavily_key ),
			'tavily_search_depth'   => in_array( (string) ( $input['tavily_search_depth'] ?? '' ), array( 'basic', 'advanced' ), true ) ? (string) $input['tavily_search_depth'] : 'basic',
			'tavily_include_answer' => ! empty( $input['tavily_include_answer'] ),
			'tavily_include_raw'    => ! empty( $input['tavily_include_raw'] ),
			'tavily_include_images' => ! empty( $input['tavily_include_images'] ),
			'image_provider'        => 'unsplash',
			'unsplash_access_key'   => $this->sanitize_secret_input( $input, $current, 'unsplash_access_key', $clear_unsplash_key ),
			'unsplash_utm_source'   => sanitize_key( (string) ( $input['unsplash_utm_source'] ?? $defaults['unsplash_utm_source'] ) ),
			'vector_provider'       => 'qdrant',
			'qdrant_endpoint'       => untrailingslashit( esc_url_raw( (string) ( $input['qdrant_endpoint'] ?? '' ) ) ),
			'qdrant_api_key'        => $this->sanitize_secret_input( $input, $current, 'qdrant_api_key', $clear_qdrant_key ),
			'qdrant_collection'     => sanitize_text_field( (string) ( $input['qdrant_collection'] ?? '' ) ),
			'qdrant_vector_name'    => sanitize_text_field( (string) ( $input['qdrant_vector_name'] ?? '' ) ),
			'embedding_provider'    => $embedding_provider,
			'embedding_dimensions'  => max( 1, min( 4096, $embedding_dimensions ) ),
			'siliconflow_api_key'   => $this->sanitize_secret_input( $input, $current, 'siliconflow_api_key', $clear_siliconflow_key ),
			'siliconflow_base_url'  => untrailingslashit( esc_url_raw( (string) ( $input['siliconflow_base_url'] ?? $defaults['siliconflow_base_url'] ) ) ),
			'siliconflow_model'     => sanitize_text_field( (string) ( $input['siliconflow_model'] ?? $defaults['siliconflow_model'] ) ),
			'jina_api_key'          => $this->sanitize_secret_input( $input, $current, 'jina_api_key', $clear_jina_key ),
			'jina_base_url'         => untrailingslashit( esc_url_raw( (string) ( $input['jina_base_url'] ?? $defaults['jina_base_url'] ) ) ),
			'jina_model'            => sanitize_text_field( (string) ( $input['jina_model'] ?? $defaults['jina_model'] ) ),
			'include_raw_responses' => ! empty( $input['include_raw_responses'] ),
			'enable_web_research'   => ! empty( $input['enable_web_research'] ),
			'enable_image_source'   => ! empty( $input['enable_image_source'] ),
			'enable_vector_search'  => ! empty( $input['enable_vector_search'] ),
		);

		return $sanitized;
	}

	private function get_secret( string $option_key, string $constant_name, string $env_name ): string {
		if ( defined( $constant_name ) && '' !== (string) constant( $constant_name ) ) {
			return (string) constant( $constant_name );
		}

		$env_key = getenv( $env_name );
		if ( is_string( $env_key ) && '' !== $env_key ) {
			return $env_key;
		}

		return (string) $this->get( $option_key );
	}

	private function sanitize_secret_input( array $input, array $current, string $key, bool $clear ): string {
		if ( $clear ) {
			return '';
		}

		$new_key = isset( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : '';
		if ( '' !== $new_key ) {
			return sanitize_text_field( $new_key );
		}

		return (string) ( $current[ $key ] ?? '' );
	}
}
