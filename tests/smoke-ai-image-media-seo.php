<?php
/**
 * Local WordPress smoke for AI image media SEO normalization.
 *
 * Run with WP-CLI:
 * wp eval-file tests/smoke-ai-image-media-seo.php
 *
 * @package Npcink_Toolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "FAIL: Run this script through WP-CLI eval-file so WordPress is loaded.\n" );
	exit( 1 );
}

function toolbox_ai_image_media_seo_smoke_pass( string $message ): void {
	echo "PASS: {$message}\n";
}

function toolbox_ai_image_media_seo_smoke_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function toolbox_ai_image_media_seo_smoke_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		toolbox_ai_image_media_seo_smoke_fail( $message );
	}

	toolbox_ai_image_media_seo_smoke_pass( $message );
}

function toolbox_ai_image_media_seo_smoke_admin_user_id(): int {
	$users = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'fields'  => 'ID',
		)
	);

	return absint( $users[0] ?? 0 );
}

add_filter(
	'npcink_toolbox_ai_image_generation_cloud_request',
	static function () {
		return array(
			'status' => 'ready',
			'data'   => array(
				'result' => array(
					'status'     => 'ready',
					'model_id'   => 'grok-imagine-image-quality',
					'profile_id' => 'grok-imagine-image-quality',
					'images'     => array(
						array(
							'id'            => 'seo-smoke-generated-image',
							'regular_url'   => 'https://example.test/generated/seo-smoke.jpg',
							'title'         => 'Create a publication-safe editorial illustration for a technology article: a desk scene with abstract AI cards.',
							'description'   => 'Source context: selected paragraph. Visual task: translate the context into a concrete editorial scene.',
							'alt'           => 'Create an original editorial image for a WordPress article.',
							'prompt'        => 'Create a publication-safe editorial illustration for a technology article: a desk scene with abstract AI cards.',
						),
					),
				),
			),
		);
	},
	10,
	0
);

wp_set_current_user( toolbox_ai_image_media_seo_smoke_admin_user_id() );

$request = new WP_REST_Request( 'POST', '/npcink-toolbox/v1/ai/image-generation' );
$request->set_param( 'prompt', 'Create a publication-safe editorial illustration for a technology article: a desk scene with abstract AI cards.' );
$request->set_param( 'aspect_ratio', '16:9' );
$request->set_param( 'response_format', 'url' );
$request->set_param( 'n', 1 );
$request->set_param(
	'media_context',
	array(
		'title'       => 'SEO、AEO、GEO 怎么喂给 AI',
		'alt'         => '',
		'description' => '',
	)
);
$request->set_param(
	'post_context',
	array(
		'title'               => 'SEO、AEO、GEO 怎么喂给 AI',
		'selected_text'       => 'AEO 关注回答型体验。读者或搜索系统提出一个明确问题时，文章不能先给直接答案，再补充条件、步骤和限制。',
		'selected_block_text' => 'AEO 关注回答型体验。读者或搜索系统提出一个明确问题时，文章不能先给直接答案，再补充条件、步骤和限制。',
	)
);

$response = rest_do_request( $request );
if ( is_wp_error( $response ) ) {
	toolbox_ai_image_media_seo_smoke_fail( 'REST dispatch returned WP_Error: ' . $response->get_error_code() );
}

$data = $response->get_data();
toolbox_ai_image_media_seo_smoke_assert( $response->get_status() >= 200 && $response->get_status() < 300, 'AI image generation REST dispatch succeeds with mocked Cloud runtime.' );

$image = $data['images'][0] ?? array();
toolbox_ai_image_media_seo_smoke_assert( is_array( $image ) && 'ai_generated' === (string) ( $image['source_type'] ?? '' ), 'AI image smoke returns an AI-generated image candidate.' );
toolbox_ai_image_media_seo_smoke_assert( 'SEO、AEO、GEO 怎么喂给 AI' === (string) ( $image['title'] ?? '' ), 'AI image media title uses reviewed article context instead of prompt instructions.' );
toolbox_ai_image_media_seo_smoke_assert( false === strpos( strtolower( (string) ( $image['description'] ?? '' ) ), 'source context:' ), 'AI image media description strips prompt-planning text.' );
toolbox_ai_image_media_seo_smoke_assert( false === strpos( strtolower( (string) ( $image['alt_description'] ?? '' ) ), 'create an original' ), 'AI image ALT text strips prompt instructions.' );
toolbox_ai_image_media_seo_smoke_assert( 'reviewed_article_context' === (string) ( $image['seo_suggestions']['basis'] ?? '' ), 'AI image SEO suggestions record reviewed article context as the basis.' );

