<?php
/**
 * Uninstall cleanup for Magick AI Toolbox.
 *
 * @package Magick_AI_Toolbox
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'magick_ai_toolbox_settings' );
delete_option( 'magick_ai_toolbox_content_context' );
