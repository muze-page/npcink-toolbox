<?php
/**
 * Local WordPress operator trial for the media ALT/caption review set.
 *
 * Run with WP-CLI:
 * wp eval-file tests/smoke-media-alt-caption-trial.php
 *
 * @package Npcink_Toolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "FAIL: Run this script through WP-CLI eval-file so WordPress is loaded.\n" );
	exit( 1 );
}

function toolbox_media_alt_trial_pass( string $message ): void {
	echo "PASS: {$message}\n";
}

function toolbox_media_alt_trial_fail( string $message ): void {
	fwrite( STDERR, "FAIL: {$message}\n" );
	exit( 1 );
}

function toolbox_media_alt_trial_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		toolbox_media_alt_trial_fail( $message );
	}

	toolbox_media_alt_trial_pass( $message );
}

function toolbox_media_alt_trial_admin_user_id(): int {
	$admins = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'fields'  => 'ID',
		)
	);

	return absint( $admins[0] ?? 0 );
}

function toolbox_media_alt_trial_attachment_ids( int $limit ): array {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => max( 1, min( 20, $limit ) ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	return array_values( array_map( 'absint', is_array( $ids ) ? $ids : array() ) );
}

function toolbox_media_alt_trial_attachment_snapshot( array $attachment_ids ): array {
	$snapshot = array();

	foreach ( $attachment_ids as $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$post          = get_post( $attachment_id );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			continue;
		}

		$url      = wp_get_attachment_url( $attachment_id );
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$snapshot[ $attachment_id ] = array(
			'post_title'       => (string) $post->post_title,
			'post_excerpt'     => (string) $post->post_excerpt,
			'post_content'     => (string) $post->post_content,
			'post_modified_gmt' => (string) $post->post_modified_gmt,
			'alt'              => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'attached_file'    => (string) get_post_meta( $attachment_id, '_wp_attached_file', true ),
			'metadata_hash'    => md5( wp_json_encode( is_array( $metadata ) ? $metadata : array() ) ?: '' ),
			'url'              => is_string( $url ) ? $url : '',
		);
	}

	return $snapshot;
}

function toolbox_media_alt_trial_rest_request( array $payload ): array {
	$request = new WP_REST_Request( 'POST', '/npcink-toolbox/v1/ai/site-helpers' );
	$request->set_body_params( $payload );
	$response = rest_do_request( $request );
	if ( is_wp_error( $response ) ) {
		toolbox_media_alt_trial_fail( 'REST request failed: ' . $response->get_error_code() );
	}

	$data = rest_get_server()->response_to_data( $response, false );
	if ( ! is_array( $data ) ) {
		toolbox_media_alt_trial_fail( 'REST response is not an array.' );
	}

	return $data;
}

$admin_user_id = toolbox_media_alt_trial_admin_user_id();
toolbox_media_alt_trial_assert( $admin_user_id > 0, 'Found an administrator user for the media ALT/caption trial.' );
wp_set_current_user( $admin_user_id );

$max_items      = max( 1, min( 10, absint( getenv( 'NPCINK_TOOLBOX_MEDIA_ALT_TRIAL_MAX_ITEMS' ) ?: 10 ) ) );
$attachment_ids = toolbox_media_alt_trial_attachment_ids( $max_items );
toolbox_media_alt_trial_assert( ! empty( $attachment_ids ), 'Found real image attachments for the media ALT/caption trial.' );

$before = toolbox_media_alt_trial_attachment_snapshot( $attachment_ids );
toolbox_media_alt_trial_assert( count( $before ) > 0, 'Captured before snapshot for real image attachments.' );

$cloud_filter_calls = 0;
add_filter(
	'npcink_toolbox_hosted_ai_site_helper_cloud_request',
	static function ( $handled, array $runtime_payload, array $input ) use ( &$cloud_filter_calls ) {
		++$cloud_filter_calls;
		toolbox_media_alt_trial_assert( 'media_alt_suggestions' === (string) ( $input['intent'] ?? '' ), 'Trial host filter receives the media ALT suggestions intent.' );

		return array(
			'status' => 'ready',
			'run_id' => 'local_media_alt_caption_trial',
			'result' => array(
				'status'      => 'ready',
				'model_id'    => 'local_trial_no_cloud_runtime',
				'output_text' => 'Local operator trial: use the metadata-only review set and visually confirm every selected item.',
			),
		);
	},
	10,
	3
);

$data = toolbox_media_alt_trial_rest_request(
	array(
		'intent'           => 'media_alt_suggestions',
		'focus'            => 'Local real media library operator trial',
		'review_set_limit' => $max_items,
		'source_policy'    => 'media_library_metadata_only_no_pixel_vision',
	)
);

toolbox_media_alt_trial_assert( 1 === $cloud_filter_calls, 'Trial used a local host filter instead of requiring Cloud runtime availability.' );
toolbox_media_alt_trial_assert( false === (bool) ( $data['direct_wordpress_write'] ?? true ), 'Site-helper response keeps direct WordPress writes disabled.' );
toolbox_media_alt_trial_assert( 'suggestion_only' === (string) ( $data['write_posture'] ?? '' ), 'Site-helper response remains suggestion-only.' );

$review_set = is_array( $data['media_alt_caption_review_set'] ?? null ) ? $data['media_alt_caption_review_set'] : array();
toolbox_media_alt_trial_assert( 'media_alt_caption_review_set.v1' === (string) ( $review_set['contract_version'] ?? '' ), 'Trial response returns the media ALT/caption review-set contract.' );
toolbox_media_alt_trial_assert( 'media_library_metadata_only_no_pixel_vision' === (string) ( $review_set['source_policy'] ?? '' ), 'Trial uses metadata-only source policy.' );
toolbox_media_alt_trial_assert( false === (bool) ( $review_set['direct_wordpress_write'] ?? true ), 'Review set does not authorize direct WordPress writes.' );
toolbox_media_alt_trial_assert( false === (bool) ( $review_set['proposal_created'] ?? true ), 'Review set does not create a proposal.' );
toolbox_media_alt_trial_assert( false === (bool) ( $review_set['execution_created'] ?? true ), 'Review set does not create an execution.' );
toolbox_media_alt_trial_assert( false === (bool) ( $review_set['safety']['media_derivative_run_created'] ?? true ), 'Review set does not create a media derivative run.' );

$summary = is_array( $review_set['eligibility_summary'] ?? null ) ? $review_set['eligibility_summary'] : array();
$selected = is_array( $review_set['selected_items'] ?? null ) ? $review_set['selected_items'] : array();
$blocked  = is_array( $review_set['blocked_items'] ?? null ) ? $review_set['blocked_items'] : array();

toolbox_media_alt_trial_assert( (int) ( $summary['scanned_count'] ?? 0 ) === count( $before ), 'Trial scanned the real attachment sample.' );
toolbox_media_alt_trial_assert( count( $selected ) <= $max_items, 'Trial selected count stays within the configured cap.' );
toolbox_media_alt_trial_assert( (int) ( $summary['selected_count'] ?? -1 ) === count( $selected ), 'Eligibility summary selected count matches selected items.' );
toolbox_media_alt_trial_assert( (int) ( $summary['blocked_count'] ?? -1 ) === count( $blocked ), 'Eligibility summary blocked count matches blocked items.' );

foreach ( $selected as $item ) {
	toolbox_media_alt_trial_assert( true === (bool) ( $item['needs_human_visual_check'] ?? false ), 'Every selected item requires human visual review.' );
	toolbox_media_alt_trial_assert( false === (bool) ( $item['direct_wordpress_write'] ?? true ), 'Selected item keeps direct writes disabled.' );
}

$after = toolbox_media_alt_trial_attachment_snapshot( $attachment_ids );
foreach ( $before as $attachment_id => $before_item ) {
	$after_item = $after[ $attachment_id ] ?? array();
	toolbox_media_alt_trial_assert( $before_item === $after_item, 'Attachment ' . $attachment_id . ' metadata snapshot is unchanged.' );
}

$reason_counts = array();
foreach ( $selected as $item ) {
	foreach ( (array) ( $item['review_reasons'] ?? array() ) as $reason ) {
		$reason = sanitize_key( (string) $reason );
		if ( '' !== $reason ) {
			$reason_counts[ $reason ] = ( $reason_counts[ $reason ] ?? 0 ) + 1;
		}
	}
}
foreach ( $blocked as $item ) {
	$reason = sanitize_key( (string) ( $item['blocked_reason'] ?? '' ) );
	if ( '' !== $reason ) {
		$reason_counts[ 'blocked_' . $reason ] = ( $reason_counts[ 'blocked_' . $reason ] ?? 0 ) + 1;
	}
}

echo 'INFO: Media ALT/caption trial summary=' . wp_json_encode(
	array(
		'scanned'       => (int) ( $summary['scanned_count'] ?? 0 ),
		'eligible'      => (int) ( $summary['eligible_count'] ?? 0 ),
		'selected'      => count( $selected ),
		'blocked'       => count( $blocked ),
		'max_items'     => $max_items,
		'reason_counts' => $reason_counts,
	)
) . PHP_EOL;
echo "Media ALT/caption operator trial passed.\n";
