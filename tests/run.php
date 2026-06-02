<?php
/**
 * Static contract checks for the first Toolbox release.
 *
 * @package Magick_AI_Toolbox
 */

$root = dirname( __DIR__ );

function toolbox_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}

	echo "PASS: {$message}\n";
}

$main = file_get_contents( $root . '/magick-ai-toolbox.php' );
toolbox_assert( false !== $main && str_contains( $main, 'Plugin Name: Magick AI Toolbox' ), 'Plugin header is present.' );

$rest = file_get_contents( $root . '/includes/Rest_Controller.php' );
foreach ( array( '/web-research', '/image-candidates', '/knowledge-search', '/flows/article-brief', '/flows/media-brief' ) as $route ) {
	toolbox_assert( false !== strpos( $rest, $route ), "REST route {$route} is registered." );
}

$abilities = file_get_contents( $root . '/includes/Abilities.php' );
foreach ( array( 'magick-ai-toolbox/web-research', 'magick-ai-toolbox/generate-image-candidate', 'magick-ai-toolbox/knowledge-search', 'magick-ai-toolbox/build-article-brief', 'magick-ai-toolbox/build-media-brief' ) as $ability_id ) {
	toolbox_assert( false !== strpos( $abilities, $ability_id ), "Ability {$ability_id} is registered." );
}

$client = file_get_contents( $root . '/includes/OpenAI_Client.php' );
toolbox_assert( false !== strpos( $client, "'type'                => 'web_search'" ), 'Web research uses Responses web_search tool.' );
toolbox_assert( false !== strpos( $client, "'type'             => 'file_search'" ), 'Knowledge search uses Responses file_search tool.' );
toolbox_assert( false !== strpos( $client, "'/images/generations'" ), 'Image candidates use image generation endpoint.' );

toolbox_assert( false === strpos( $client, 'write_confirmed' ), 'Legacy write_confirmed contract is absent.' );
toolbox_assert( false === strpos( $client, 'confirm_token' ), 'Legacy confirm_token contract is absent.' );

echo "Static contract checks passed.\n";
