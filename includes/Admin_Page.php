<?php
/**
 * WordPress admin page for Toolbox actions.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

defined( 'ABSPATH' ) || exit;

final class Admin_Page {
	private const PARENT_MENU_SLUG = 'magick-ai';
	private const MENU_SLUG        = 'magick-ai-toolbox';

	private Settings $settings;
	private string $hook_suffix = '';

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register_menu(): void {
		if ( $this->has_magick_parent_menu() ) {
			$this->hook_suffix = add_submenu_page(
				self::PARENT_MENU_SLUG,
				__( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
				__( 'Toolbox', 'magick-ai-toolbox' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render' ),
				45
			);
			return;
		}

		$this->hook_suffix = add_management_page(
			__( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
			__( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	private function has_magick_parent_menu(): bool {
		global $menu;

		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && self::PARENT_MENU_SLUG === $item[2] ) {
				return true;
			}
		}

		return false;
	}

	public function enqueue( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'magick-ai-toolbox-admin',
			MAGICK_AI_TOOLBOX_URL . 'assets/admin.css',
			array(),
			MAGICK_AI_TOOLBOX_VERSION
		);

		wp_enqueue_script(
			'magick-ai-toolbox-admin',
			MAGICK_AI_TOOLBOX_URL . 'assets/admin.js',
			array(),
			MAGICK_AI_TOOLBOX_VERSION,
			true
		);

		wp_add_inline_script(
			'magick-ai-toolbox-admin',
			'window.MagickAIToolbox = ' . wp_json_encode(
				array(
					'restUrl' => esc_url_raw( rest_url( Plugin::REST_NAMESPACE ) ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'labels'  => array(
						'running' => __( 'Running...', 'magick-ai-toolbox' ),
						'error'   => __( 'Request failed.', 'magick-ai-toolbox' ),
					),
				)
			) . ';',
			'before'
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'magick-ai-toolbox' ) );
		}

		$settings = $this->settings->get_all();
		?>
		<div class="wrap magick-ai-toolbox">
			<h1><?php esc_html_e( 'Magick AI Toolbox', 'magick-ai-toolbox' ); ?></h1>

			<div class="magick-ai-toolbox__layout">
				<section class="magick-ai-toolbox__main" aria-label="<?php esc_attr_e( 'Toolbox actions', 'magick-ai-toolbox' ); ?>">
					<?php $this->render_tool_cards(); ?>
				</section>

				<aside class="magick-ai-toolbox__side" aria-label="<?php esc_attr_e( 'Provider settings', 'magick-ai-toolbox' ); ?>">
					<h2><?php esc_html_e( 'Connectors', 'magick-ai-toolbox' ); ?></h2>
					<form method="post" action="options.php">
						<?php settings_fields( 'magick_ai_toolbox' ); ?>

						<label>
							<span><?php esc_html_e( 'Tavily API key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[tavily_api_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $this->settings->has_tavily_api_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'tvly-...', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_tavily_api_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored Tavily key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'Tavily search depth', 'magick-ai-toolbox' ); ?></span>
							<select name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[tavily_search_depth]">
								<option value="basic" <?php selected( (string) $settings['tavily_search_depth'], 'basic' ); ?>><?php esc_html_e( 'Basic', 'magick-ai-toolbox' ); ?></option>
								<option value="advanced" <?php selected( (string) $settings['tavily_search_depth'], 'advanced' ); ?>><?php esc_html_e( 'Advanced', 'magick-ai-toolbox' ); ?></option>
							</select>
						</label>

						<?php $this->render_checkbox( 'tavily_include_answer', __( 'Tavily answer summary', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'tavily_include_raw', __( 'Tavily raw content', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'tavily_include_images', __( 'Tavily image URLs', 'magick-ai-toolbox' ), $settings ); ?>

						<label>
							<span><?php esc_html_e( 'Unsplash access key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[unsplash_access_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $this->settings->has_unsplash_access_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'Unsplash access key', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_unsplash_access_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored Unsplash key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'Unsplash app name', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[unsplash_utm_source]" value="<?php echo esc_attr( (string) $settings['unsplash_utm_source'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'Qdrant endpoint', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[qdrant_endpoint]" value="<?php echo esc_attr( (string) $settings['qdrant_endpoint'] ); ?>" placeholder="https://example.qdrant.io" />
						</label>

						<label>
							<span><?php esc_html_e( 'Qdrant collection', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[qdrant_collection]" value="<?php echo esc_attr( (string) $settings['qdrant_collection'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'Qdrant vector name', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[qdrant_vector_name]" value="<?php echo esc_attr( (string) $settings['qdrant_vector_name'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'Qdrant API key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[qdrant_api_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( '' !== $this->settings->get_qdrant_api_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'Optional for local Qdrant', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_qdrant_api_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored Qdrant key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'Embedding provider', 'magick-ai-toolbox' ); ?></span>
							<select name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[embedding_provider]">
								<option value="siliconflow" <?php selected( (string) $settings['embedding_provider'], 'siliconflow' ); ?>><?php esc_html_e( 'SiliconFlow', 'magick-ai-toolbox' ); ?></option>
								<option value="jina" <?php selected( (string) $settings['embedding_provider'], 'jina' ); ?>><?php esc_html_e( 'Jina AI', 'magick-ai-toolbox' ); ?></option>
							</select>
						</label>

						<label>
							<span><?php esc_html_e( 'Embedding dimensions', 'magick-ai-toolbox' ); ?></span>
							<input type="number" min="1" max="4096" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[embedding_dimensions]" value="<?php echo esc_attr( (string) $settings['embedding_dimensions'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'SiliconFlow API key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[siliconflow_api_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $this->settings->has_siliconflow_api_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'SiliconFlow API key', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_siliconflow_api_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored SiliconFlow key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'SiliconFlow base URL', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[siliconflow_base_url]" value="<?php echo esc_attr( (string) $settings['siliconflow_base_url'] ); ?>" placeholder="https://api.siliconflow.com/v1" />
						</label>

						<label>
							<span><?php esc_html_e( 'SiliconFlow embedding model', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[siliconflow_model]" value="<?php echo esc_attr( (string) $settings['siliconflow_model'] ); ?>" placeholder="BAAI/bge-m3" />
						</label>

						<label>
							<span><?php esc_html_e( 'Jina AI API key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[jina_api_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $this->settings->has_jina_api_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'Jina AI API key', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_jina_api_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored Jina AI key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'Jina AI base URL', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[jina_base_url]" value="<?php echo esc_attr( (string) $settings['jina_base_url'] ); ?>" placeholder="https://api.jina.ai/v1" />
						</label>

						<label>
							<span><?php esc_html_e( 'Jina AI embedding model', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[jina_model]" value="<?php echo esc_attr( (string) $settings['jina_model'] ); ?>" placeholder="jina-embeddings-v3" />
						</label>

						<?php $this->render_checkbox( 'include_raw_responses', __( 'Include provider raw responses', 'magick-ai-toolbox' ), $settings ); ?>

						<?php $this->render_checkbox( 'enable_web_research', __( 'Web research', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'enable_image_source', __( 'Image source search', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'enable_vector_search', __( 'Vector search', 'magick-ai-toolbox' ), $settings ); ?>

						<?php submit_button( __( 'Save settings', 'magick-ai-toolbox' ) ); ?>
					</form>
				</aside>
			</div>
		</div>
		<?php
	}

	private function render_tool_cards(): void {
		?>
		<div class="magick-ai-toolbox__grid">
			<?php
			$this->render_text_tool(
				'web-research',
				__( 'Web Research', 'magick-ai-toolbox' ),
				__( 'Search Tavily and return source-aware research notes.', 'magick-ai-toolbox' ),
				'query',
				__( 'What should be researched?', 'magick-ai-toolbox' ),
				__( 'Research', 'magick-ai-toolbox' ),
				array(
					array(
						'name'        => 'include_domains',
						'label'       => __( 'Include domains', 'magick-ai-toolbox' ),
						'placeholder' => 'example.com, wordpress.org',
					),
					array(
						'name'        => 'exclude_domains',
						'label'       => __( 'Exclude domains', 'magick-ai-toolbox' ),
						'placeholder' => 'example.com',
					),
				)
			);

			$this->render_text_tool(
				'image-candidates',
				__( 'Unsplash Image Candidates', 'magick-ai-toolbox' ),
				__( 'Search image-source candidates and preserve attribution metadata.', 'magick-ai-toolbox' ),
				'query',
				__( 'Image search query', 'magick-ai-toolbox' ),
				__( 'Search images', 'magick-ai-toolbox' ),
				array(
					array(
						'name'        => 'orientation',
						'label'       => __( 'Orientation', 'magick-ai-toolbox' ),
						'placeholder' => 'landscape',
					),
				)
			);

			$this->render_text_tool(
				'knowledge-search',
				__( 'Qdrant Vector Search', 'magick-ai-toolbox' ),
				__( 'Query the configured vector collection with text embedding or vector JSON.', 'magick-ai-toolbox' ),
				'query',
				__( 'Search query or vector JSON', 'magick-ai-toolbox' ),
				__( 'Search vectors', 'magick-ai-toolbox' ),
				array(
					array(
						'name'        => 'max_results',
						'label'       => __( 'Max results', 'magick-ai-toolbox' ),
						'placeholder' => '4',
					),
				)
			);

			$this->render_text_tool(
				'flows/article-brief',
				__( 'Article Brief', 'magick-ai-toolbox' ),
				__( 'Build a research-backed outline, source notes, image prompt, and governance handoff.', 'magick-ai-toolbox' ),
				'topic',
				__( 'Article topic', 'magick-ai-toolbox' ),
				__( 'Build brief', 'magick-ai-toolbox' )
			);

			$this->render_text_tool(
				'flows/media-brief',
				__( 'Media Brief', 'magick-ai-toolbox' ),
				__( 'Use an existing post id to plan image prompts and media SEO actions.', 'magick-ai-toolbox' ),
				'post_id',
				__( 'Post ID', 'magick-ai-toolbox' ),
				__( 'Plan media', 'magick-ai-toolbox' )
			);
			?>
		</div>
		<?php
	}

	private function render_text_tool( string $endpoint, string $title, string $description, string $field, string $placeholder, string $button, array $extra_fields = array() ): void {
		?>
		<form class="magick-ai-toolbox__card" data-toolbox-endpoint="<?php echo esc_attr( $endpoint ); ?>">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $description ); ?></p>
			<label>
				<span><?php echo esc_html( $placeholder ); ?></span>
				<textarea name="<?php echo esc_attr( $field ); ?>" rows="4"></textarea>
			</label>
			<?php foreach ( $extra_fields as $extra ) : ?>
				<label>
					<span><?php echo esc_html( (string) $extra['label'] ); ?></span>
					<input type="text" name="<?php echo esc_attr( (string) $extra['name'] ); ?>" placeholder="<?php echo esc_attr( (string) $extra['placeholder'] ); ?>" />
				</label>
			<?php endforeach; ?>
			<button type="submit" class="button button-primary"><?php echo esc_html( $button ); ?></button>
			<pre class="magick-ai-toolbox__result" aria-live="polite"></pre>
		</form>
		<?php
	}

	private function render_checkbox( string $key, string $label, array $settings ): void {
		?>
		<label class="magick-ai-toolbox__check">
			<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
			<span><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}
}
