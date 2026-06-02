<?php
/**
 * WordPress admin page for Toolbox actions.
 *
 * @package Magick_AI_Toolbox
 */

namespace Magick_AI_Toolbox;

defined( 'ABSPATH' ) || exit;

final class Admin_Page {
	private Settings $settings;
	private string $hook_suffix = '';

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register_menu(): void {
		$this->hook_suffix = add_management_page(
			__( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
			__( 'Magick AI Toolbox', 'magick-ai-toolbox' ),
			'manage_options',
			'magick-ai-toolbox',
			array( $this, 'render' )
		);
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
					<h2><?php esc_html_e( 'Provider', 'magick-ai-toolbox' ); ?></h2>
					<form method="post" action="options.php">
						<?php settings_fields( 'magick_ai_toolbox' ); ?>

						<label>
							<span><?php esc_html_e( 'OpenAI API key', 'magick-ai-toolbox' ); ?></span>
							<input type="password" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[openai_api_key]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $this->settings->has_api_key() ? __( 'Stored or provided by environment', 'magick-ai-toolbox' ) : __( 'sk-...', 'magick-ai-toolbox' ) ); ?>" />
						</label>

						<label class="magick-ai-toolbox__check">
							<input type="checkbox" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[clear_openai_api_key]" value="1" />
							<span><?php esc_html_e( 'Clear stored API key', 'magick-ai-toolbox' ); ?></span>
						</label>

						<label>
							<span><?php esc_html_e( 'Text model', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[openai_model]" value="<?php echo esc_attr( (string) $settings['openai_model'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'Image model', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[openai_image_model]" value="<?php echo esc_attr( (string) $settings['openai_image_model'] ); ?>" />
						</label>

						<label>
							<span><?php esc_html_e( 'Vector store id', 'magick-ai-toolbox' ); ?></span>
							<input type="text" name="<?php echo esc_attr( Plugin::OPTION_NAME ); ?>[openai_vector_store_id]" value="<?php echo esc_attr( (string) $settings['openai_vector_store_id'] ); ?>" />
						</label>

						<?php $this->render_checkbox( 'enable_web_research', __( 'Web research', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'enable_image_generation', __( 'Image candidates', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'enable_knowledge_search', __( 'Knowledge search', 'magick-ai-toolbox' ), $settings ); ?>
						<?php $this->render_checkbox( 'external_web_access', __( 'Live web access', 'magick-ai-toolbox' ), $settings ); ?>

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
				__( 'Research a topic and return source-aware notes.', 'magick-ai-toolbox' ),
				'query',
				__( 'What should be researched?', 'magick-ai-toolbox' ),
				__( 'Research', 'magick-ai-toolbox' ),
				array(
					array(
						'name'        => 'domains',
						'label'       => __( 'Allowed domains', 'magick-ai-toolbox' ),
						'placeholder' => 'example.com, wordpress.org',
					),
				)
			);

			$this->render_text_tool(
				'image-candidates',
				__( 'Image Candidates', 'magick-ai-toolbox' ),
				__( 'Generate a visual candidate as a base64 image payload.', 'magick-ai-toolbox' ),
				'prompt',
				__( 'Describe the image.', 'magick-ai-toolbox' ),
				__( 'Generate image', 'magick-ai-toolbox' ),
				array(
					array(
						'name'        => 'size',
						'label'       => __( 'Size', 'magick-ai-toolbox' ),
						'placeholder' => '1024x1024',
					),
				)
			);

			$this->render_text_tool(
				'knowledge-search',
				__( 'Knowledge Search', 'magick-ai-toolbox' ),
				__( 'Search the configured vector store and return a grounded answer.', 'magick-ai-toolbox' ),
				'query',
				__( 'What should be searched?', 'magick-ai-toolbox' ),
				__( 'Search knowledge', 'magick-ai-toolbox' ),
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
