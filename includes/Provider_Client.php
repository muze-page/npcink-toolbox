<?php
/**
 * Minimal third-party provider client for Toolbox actions.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class Provider_Client {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function web_research( string $query, array $options = array() ) {
		$api_key = $this->settings->get_tavily_api_key();
		if ( '' === $api_key ) {
			return $this->missing_secret_error( 'tavily_api_key', __( 'Configure a Tavily API key before running web research.', 'magick-ai-toolbox' ) );
		}

		$body = array(
			'query'               => $query,
			'search_depth'        => (string) $this->settings->get( 'tavily_search_depth' ),
			'include_answer'      => (bool) $this->settings->get( 'tavily_include_answer' ),
			'include_raw_content' => (bool) $this->settings->get( 'tavily_include_raw' ),
			'include_images'      => (bool) $this->settings->get( 'tavily_include_images' ),
			'include_favicon'     => true,
			'max_results'         => max( 1, min( 10, (int) ( $options['max_results'] ?? 5 ) ) ),
		);

		if ( ! empty( $options['include_domains'] ) && is_array( $options['include_domains'] ) ) {
			$body['include_domains'] = array_values( $options['include_domains'] );
		}

		if ( ! empty( $options['exclude_domains'] ) && is_array( $options['exclude_domains'] ) ) {
			$body['exclude_domains'] = array_values( $options['exclude_domains'] );
		}

		if ( ! empty( $options['time_range'] ) && in_array( $options['time_range'], array( 'day', 'week', 'month', 'year' ), true ) ) {
			$body['time_range'] = $options['time_range'];
		}

		$response = $this->json_request(
			'https://api.tavily.com/search',
			'POST',
			array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			$body
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$results = array();
		foreach ( (array) ( $response['results'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$results[] = array(
				'title'       => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'url'         => esc_url_raw( (string) ( $item['url'] ?? '' ) ),
				'content'     => sanitize_textarea_field( (string) ( $item['content'] ?? '' ) ),
				'raw_content' => isset( $item['raw_content'] ) ? sanitize_textarea_field( (string) $item['raw_content'] ) : '',
				'score'       => isset( $item['score'] ) ? (float) $item['score'] : 0.0,
				'favicon'     => esc_url_raw( (string) ( $item['favicon'] ?? '' ) ),
			);
		}

		return $this->with_optional_raw(
			array(
				'provider' => 'tavily',
				'query'    => $query,
				'answer'   => sanitize_textarea_field( (string) ( $response['answer'] ?? '' ) ),
				'results'  => $results,
				'images'   => is_array( $response['images'] ?? null ) ? $response['images'] : array(),
			),
			$response
		);
	}

	public function image_candidates( string $query, array $options = array() ) {
		$access_key = $this->settings->get_unsplash_access_key();
		if ( '' === $access_key ) {
			return $this->missing_secret_error( 'unsplash_access_key', __( 'Configure an Unsplash access key before searching image candidates.', 'magick-ai-toolbox' ) );
		}

		$params = array(
			'query'    => $query,
			'per_page' => max( 1, min( 30, (int) ( $options['per_page'] ?? 8 ) ) ),
		);

		foreach ( array( 'orientation', 'color' ) as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$params[ $key ] = sanitize_key( (string) $options[ $key ] );
			}
		}

		$response = $this->json_request(
			add_query_arg( $params, 'https://api.unsplash.com/search/photos' ),
			'GET',
			array(
				'Authorization' => 'Client-ID ' . $access_key,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$utm_source = sanitize_key( (string) $this->settings->get( 'unsplash_utm_source' ) );
		$images     = array();
		foreach ( (array) ( $response['results'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$user = is_array( $item['user'] ?? null ) ? $item['user'] : array();
			$urls = is_array( $item['urls'] ?? null ) ? $item['urls'] : array();
			$links = is_array( $item['links'] ?? null ) ? $item['links'] : array();
			$profile_url = (string) ( $user['links']['html'] ?? '' );
			if ( '' !== $profile_url && '' !== $utm_source ) {
				$profile_url = add_query_arg( array( 'utm_source' => $utm_source, 'utm_medium' => 'referral' ), $profile_url );
			}

			$images[] = array(
				'id'                => sanitize_text_field( (string) ( $item['id'] ?? '' ) ),
				'description'       => sanitize_textarea_field( (string) ( $item['description'] ?? $item['alt_description'] ?? '' ) ),
				'alt_description'   => sanitize_textarea_field( (string) ( $item['alt_description'] ?? '' ) ),
				'thumb_url'         => esc_url_raw( (string) ( $urls['thumb'] ?? '' ) ),
				'small_url'         => esc_url_raw( (string) ( $urls['small'] ?? '' ) ),
				'regular_url'       => esc_url_raw( (string) ( $urls['regular'] ?? '' ) ),
				'html_url'          => esc_url_raw( (string) ( $links['html'] ?? '' ) ),
				'download_location' => esc_url_raw( (string) ( $links['download_location'] ?? '' ) ),
				'photographer'      => sanitize_text_field( (string) ( $user['name'] ?? '' ) ),
				'photographer_url'  => esc_url_raw( $profile_url ),
				'attribution'       => sprintf(
					/* translators: %s: photographer name. */
					__( 'Photo by %s on Unsplash.', 'magick-ai-toolbox' ),
					sanitize_text_field( (string) ( $user['name'] ?? 'Unsplash' ) )
				),
			);
		}

		return $this->with_optional_raw(
			array(
				'provider' => 'unsplash',
				'query'    => $query,
				'images'   => $images,
			),
			$response
		);
	}

	public function vector_search( string $input, int $max_results = 4, string $input_type = 'auto' ) {
		if ( '' === trim( $input ) ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_vector_input',
				__( 'A query or vector field is required for vector search.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		}

		$endpoint = untrailingslashit( (string) $this->settings->get( 'qdrant_endpoint' ) );
		$collection = trim( (string) $this->settings->get( 'qdrant_collection' ) );
		if ( '' === $endpoint || '' === $collection ) {
			return new WP_Error(
				'magick_ai_toolbox_missing_qdrant_connection',
				__( 'Configure a Qdrant endpoint and collection before running vector search.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		}

		$input_type = sanitize_key( $input_type );
		if ( ! in_array( $input_type, array( 'auto', 'text', 'vector', 'qdrant_query' ), true ) ) {
			$input_type = 'auto';
		}

		$embedding = null;
		$decoded = null;
		if ( 'text' !== $input_type ) {
			$decoded = json_decode( $input, true );
		}

		if ( is_array( $decoded ) ) {
			$resolved_input_type = $this->is_list( $decoded ) ? 'vector' : 'qdrant_query';
			if ( 'vector' === $resolved_input_type ) {
				$dimension_error = $this->validate_vector_dimensions( $decoded, __( 'The supplied vector', 'magick-ai-toolbox' ) );
				if ( is_wp_error( $dimension_error ) ) {
					return $dimension_error;
				}
			}

			$body = $this->build_qdrant_query_body( $decoded, $max_results );
		} elseif ( 'vector' === $input_type || 'qdrant_query' === $input_type ) {
			return new WP_Error(
				'magick_ai_toolbox_invalid_vector_json',
				__( 'Vector search requires a JSON array vector or a Qdrant query object when input_type is vector or qdrant_query.', 'magick-ai-toolbox' ),
				array( 'status' => 400 )
			);
		} else {
			$resolved_input_type = 'text';
			$embedding = $this->create_embedding( $input );
			if ( is_wp_error( $embedding ) ) {
				return $embedding;
			}

			$body = $this->build_qdrant_query_body( $embedding['vector'], $max_results );
		}

		$api_key = $this->settings->get_qdrant_api_key();
		$headers = array();
		if ( '' !== $api_key ) {
			$headers['api-key'] = $api_key;
		}

		$response = $this->json_request(
			$endpoint . '/collections/' . rawurlencode( $collection ) . '/points/query',
			'POST',
			$headers,
			$body
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$payload = array(
			'provider'        => 'qdrant',
			'input_type'      => $resolved_input_type,
			'collection'      => $collection,
			'points'          => is_array( $response['result']['points'] ?? null ) ? $response['result']['points'] : array(),
		);

		if ( is_array( $embedding ) ) {
			$payload['embedding_provider']   = $embedding['provider'];
			$payload['embedding_model']      = $embedding['model'];
			$payload['embedding_dimensions'] = $embedding['dimensions'];
		}

		return $this->with_optional_raw( $payload, $response );
	}

	public function build_article_brief( string $topic, bool $include_vector = true ) {
		$research  = $this->web_research( $topic, array( 'max_results' => 5 ) );
		$images    = $this->image_candidates( $topic, array( 'per_page' => 6 ) );
		$knowledge = null;
		if ( $include_vector && (bool) $this->settings->get( 'enable_vector_search' ) && $this->settings->has_qdrant_connection() ) {
			$knowledge = $this->vector_search( $topic, 4, 'text' );
		}

		return array(
			'provider'  => 'toolbox',
			'topic'     => $topic,
			'research'  => is_wp_error( $research ) ? array( 'error' => $research->get_error_message() ) : $research,
			'images'    => is_wp_error( $images ) ? array( 'error' => $images->get_error_message() ) : $images,
			'knowledge' => is_wp_error( $knowledge ) ? array( 'error' => $knowledge->get_error_message() ) : $knowledge,
			'handoff'   => array(
				'write_posture' => 'suggestion_only',
				'next_steps'    => array(
					'Review sources.',
					'Select image candidate and preserve attribution.',
					'Create WordPress draft or media proposals through Abilities/Core.',
				),
			),
		);
	}

	public function build_media_brief( string $post_context ) {
		return $this->image_candidates( $this->post_context_to_image_query( $post_context ), array( 'per_page' => 8 ) );
	}

	private function build_qdrant_query_body( array $decoded, int $max_results ): array {
		$is_vector = $this->is_list( $decoded );
		$limit = max( 1, min( 10, $max_results ) );
		$vector_name = trim( (string) $this->settings->get( 'qdrant_vector_name' ) );

		if ( $is_vector ) {
			$body = array(
				'query'        => array_map( 'floatval', $decoded ),
				'limit'        => $limit,
				'with_payload' => true,
			);

			if ( '' !== $vector_name ) {
				$body['using'] = $vector_name;
			}

			return $body;
		}

		$decoded['limit'] = isset( $decoded['limit'] ) ? max( 1, min( 10, (int) $decoded['limit'] ) ) : $limit;
		if ( ! array_key_exists( 'with_payload', $decoded ) ) {
			$decoded['with_payload'] = true;
		}
		if ( '' !== $vector_name && ! array_key_exists( 'using', $decoded ) ) {
			$decoded['using'] = $vector_name;
		}

		return $decoded;
	}

	private function create_embedding( string $query ) {
		$provider = sanitize_key( (string) $this->settings->get( 'embedding_provider' ) );
		if ( 'jina' === $provider ) {
			return $this->create_jina_embedding( $query );
		}

		return $this->create_siliconflow_embedding( $query );
	}

	private function create_siliconflow_embedding( string $query ) {
		$api_key = $this->settings->get_siliconflow_api_key();
		if ( '' === $api_key ) {
			return $this->missing_secret_error( 'siliconflow_api_key', __( 'Configure a SiliconFlow API key before running text-to-vector search.', 'magick-ai-toolbox' ) );
		}

		$base_url = untrailingslashit( (string) ( $this->settings->get( 'siliconflow_base_url' ) ?: 'https://api.siliconflow.com/v1' ) );
		$model = trim( (string) $this->settings->get( 'siliconflow_model' ) );
		if ( '' === $model ) {
			$model = 'BAAI/bge-m3';
		}

		$response = $this->json_request(
			$base_url . '/embeddings',
			'POST',
			array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			array(
				'model' => $model,
				'input' => $query,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$vector = $response['data'][0]['embedding'] ?? null;
		if ( ! is_array( $vector ) || array() === $vector ) {
			return new WP_Error(
				'magick_ai_toolbox_invalid_embedding_response',
				__( 'The embedding provider did not return an embedding vector.', 'magick-ai-toolbox' ),
				array( 'status' => 502 )
			);
		}

		$dimension_error = $this->validate_vector_dimensions( $vector, __( 'The SiliconFlow embedding', 'magick-ai-toolbox' ) );
		if ( is_wp_error( $dimension_error ) ) {
			return $dimension_error;
		}

		return array(
			'provider'   => 'siliconflow',
			'model'      => sanitize_text_field( (string) ( $response['model'] ?? $model ) ),
			'vector'     => array_map( 'floatval', $vector ),
			'dimensions' => count( $vector ),
		);
	}

	private function create_jina_embedding( string $query ) {
		$api_key = $this->settings->get_jina_api_key();
		if ( '' === $api_key ) {
			return $this->missing_secret_error( 'jina_api_key', __( 'Configure a Jina AI API key before running Jina text-to-vector search.', 'magick-ai-toolbox' ) );
		}

		$base_url = untrailingslashit( (string) ( $this->settings->get( 'jina_base_url' ) ?: 'https://api.jina.ai/v1' ) );
		$model = trim( (string) $this->settings->get( 'jina_model' ) );
		if ( '' === $model ) {
			$model = 'jina-embeddings-v3';
		}

		$body = array(
			'model' => $model,
			'input' => $query,
		);

		$dimensions = $this->expected_embedding_dimensions();
		if ( 0 < $dimensions ) {
			$body['dimensions'] = $dimensions;
		}

		$response = $this->json_request(
			$base_url . '/embeddings',
			'POST',
			array(
				'Authorization' => 'Bearer ' . $api_key,
			),
			$body
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$vector = $response['data'][0]['embedding'] ?? null;
		if ( ! is_array( $vector ) || array() === $vector ) {
			return new WP_Error(
				'magick_ai_toolbox_invalid_embedding_response',
				__( 'The embedding provider did not return an embedding vector.', 'magick-ai-toolbox' ),
				array( 'status' => 502 )
			);
		}

		$dimension_error = $this->validate_vector_dimensions( $vector, __( 'The Jina embedding', 'magick-ai-toolbox' ) );
		if ( is_wp_error( $dimension_error ) ) {
			return $dimension_error;
		}

		return array(
			'provider'   => 'jina',
			'model'      => sanitize_text_field( (string) ( $response['model'] ?? $model ) ),
			'vector'     => array_map( 'floatval', $vector ),
			'dimensions' => count( $vector ),
		);
	}

	private function expected_embedding_dimensions(): int {
		return max( 0, (int) $this->settings->get( 'embedding_dimensions' ) );
	}

	private function validate_vector_dimensions( array $vector, string $label ) {
		$expected = $this->expected_embedding_dimensions();
		if ( 0 === $expected ) {
			return null;
		}

		$actual = count( $vector );
		if ( $actual === $expected ) {
			return null;
		}

		return new WP_Error(
			'magick_ai_toolbox_embedding_dimension_mismatch',
			sprintf(
				/* translators: 1: vector label, 2: actual dimensions, 3: expected dimensions. */
				__( '%1$s has %2$d dimensions, but the configured Qdrant collection expects %3$d.', 'magick-ai-toolbox' ),
				$label,
				$actual,
				$expected
			),
			array( 'status' => 400 )
		);
	}

	private function json_request( string $url, string $method, array $headers = array(), ?array $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 45,
			'headers' => array_merge(
				array(
					'Accept' => 'application/json',
				),
				$headers
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'magick_ai_toolbox_provider_invalid_json',
				__( 'The provider returned an invalid JSON response.', 'magick-ai-toolbox' ),
				array( 'status' => 502 )
			);
		}

		if ( 200 > $status || 299 < $status ) {
			$message = sanitize_text_field( (string) ( $data['error']['message'] ?? $data['message'] ?? __( 'The provider request failed.', 'magick-ai-toolbox' ) ) );
			return new WP_Error(
				'magick_ai_toolbox_provider_error',
				$message,
				array(
					'status'          => $status,
					'provider_status' => $status,
				)
			);
		}

		return $data;
	}

	private function missing_secret_error( string $secret, string $message ): WP_Error {
		return new WP_Error(
			'magick_ai_toolbox_missing_' . sanitize_key( $secret ),
			$message,
			array( 'status' => 400 )
		);
	}

	private function with_optional_raw( array $payload, array $raw ): array {
		if ( (bool) $this->settings->get( 'include_raw_responses' ) ) {
			$payload['raw'] = $raw;
		}

		return $payload;
	}

	private function post_context_to_image_query( string $post_context ): string {
		$decoded = json_decode( $post_context, true );
		if ( is_array( $decoded ) ) {
			$title = trim( sanitize_text_field( (string) ( $decoded['title'] ?? '' ) ) );
			if ( '' !== $title ) {
				return $title;
			}

			$excerpt = trim( sanitize_textarea_field( (string) ( $decoded['excerpt'] ?? '' ) ) );
			if ( '' !== $excerpt ) {
				return wp_trim_words( $excerpt, 12, '' );
			}
		}

		return wp_trim_words( wp_strip_all_tags( $post_context ), 12, '' );
	}

	private function is_list( array $value ): bool {
		$index = 0;
		foreach ( $value as $key => $unused ) {
			unset( $unused );
			if ( $key !== $index ) {
				return false;
			}
			++$index;
		}

		return true;
	}
}
