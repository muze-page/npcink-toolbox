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

$admin_page = file_get_contents( $root . '/includes/Admin_Page.php' );
toolbox_assert( false !== strpos( $admin_page, "private const PARENT_MENU_SLUG = 'magick-ai';" ), 'Admin page targets the shared Magick AI parent menu.' );
toolbox_assert( false !== strpos( $admin_page, "private const MENU_SLUG        = 'magick-ai-toolbox';" ), 'Admin page uses stable Toolbox menu slug.' );
toolbox_assert( false !== strpos( $admin_page, 'add_submenu_page' ) && false !== strpos( $admin_page, '45' ), 'Admin page registers after Abilities and before Cloud Addon.' );
toolbox_assert( false !== strpos( $admin_page, 'add_management_page' ), 'Admin page keeps a Tools fallback for standalone installs.' );

$plugin = file_get_contents( $root . '/includes/Plugin.php' );
toolbox_assert( false !== strpos( $plugin, 'register_with_magick_ai_abilities' ) && false !== strpos( $plugin, "'wp_abilities_api_categories_init'" ) && false !== strpos( $plugin, ', 1 );' ), 'Helper ability registration is deferred to the Abilities API category hook.' );
toolbox_assert( false === strpos( $plugin, '$this->abilities->register_with_magick_ai_abilities();' ), 'Helper ability registration is not executed during plugin hook setup.' );

$rest = file_get_contents( $root . '/includes/Rest_Controller.php' );
foreach ( array( '/web-research', '/image-candidates', '/vector-search', '/knowledge-search', '/flows/article-brief', '/flows/media-brief' ) as $route ) {
	toolbox_assert( false !== strpos( $rest, $route ), "REST route {$route} is registered." );
}

$abilities = file_get_contents( $root . '/includes/Abilities.php' );
foreach ( array( 'magick-ai-toolbox/web-research', 'magick-ai-toolbox/search-image-source', 'magick-ai-toolbox/vector-search', 'magick-ai-toolbox/build-article-brief', 'magick-ai-toolbox/build-media-brief' ) as $ability_id ) {
	toolbox_assert( false !== strpos( $abilities, $ability_id ), "Ability {$ability_id} is registered." );
}

$client = file_get_contents( $root . '/includes/Provider_Client.php' );
toolbox_assert( false !== strpos( $client, 'https://api.tavily.com/search' ), 'Web research uses Tavily search.' );
toolbox_assert( false !== strpos( $client, 'https://api.unsplash.com/search/photos' ), 'Image candidates use Unsplash photo search.' );
toolbox_assert( false !== strpos( $client, '/points/query' ), 'Vector search uses Qdrant query points.' );
toolbox_assert( false !== strpos( $client, '/embeddings' ), 'Text vector search uses the configured embedding endpoint.' );
toolbox_assert( false !== strpos( $client, 'siliconflow' ), 'Embedding provider is normalized as SiliconFlow.' );
toolbox_assert( false !== strpos( $client, 'jina' ), 'Jina AI is available as an optional embedding provider.' );
toolbox_assert( false !== strpos( $client, 'embedding_dimension_mismatch' ), 'Vector search guards against embedding dimension mismatch.' );
toolbox_assert( false !== strpos( $client, 'download_location' ), 'Unsplash responses preserve download tracking location.' );
toolbox_assert( false !== strpos( $client, 'with_optional_raw' ), 'Provider raw responses are optional.' );

$settings = file_get_contents( $root . '/includes/Settings.php' );
toolbox_assert( false !== strpos( $settings, 'BAAI/bge-m3' ), 'SiliconFlow default embedding model is configured.' );
toolbox_assert( false !== strpos( $settings, 'jina-embeddings-v3' ), 'Jina default embedding model is configured.' );
toolbox_assert( false !== strpos( $settings, "'embedding_dimensions'  => 1024" ), 'Default embedding dimensions match BAAI/bge-m3 and Qdrant guidance.' );
toolbox_assert( false !== strpos( $settings, 'SILICONFLOW_API_KEY' ), 'SiliconFlow key can be provided by environment.' );
toolbox_assert( false !== strpos( $settings, 'JINA_API_KEY' ), 'Jina key can be provided by environment.' );

toolbox_assert( false !== strpos( $rest, 'siliconflow_configured' ), 'Status reports SiliconFlow configuration.' );
toolbox_assert( false !== strpos( $rest, 'jina_configured' ), 'Status reports Jina configuration.' );
toolbox_assert( false !== strpos( $rest, 'embedding_dimensions' ), 'Status reports embedding dimensions.' );
toolbox_assert( false !== strpos( $rest, 'query or vector field' ), 'Vector REST route accepts query or vector input.' );
toolbox_assert( false !== strpos( $rest, 'magick_ai_toolbox_rest_permission' ), 'REST permission can be mediated by a host scope filter.' );

toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.search' ), 'Web ability exposes the stable Toolbox search scope.' );
toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.vector_search' ), 'Vector ability exposes a Toolbox vector scope.' );
toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.workflow_suggest' ), 'Workflow abilities expose the stable Toolbox workflow scope.' );
toolbox_assert( false !== strpos( $abilities, "array( 'query' )" ), 'Vector ability accepts query input for AI callers.' );
toolbox_assert( false !== strpos( $abilities, 'magick_ai_toolbox_ability_permission' ), 'Ability permission can be mediated by a host scope filter.' );
toolbox_assert( false !== strpos( $abilities, '$this->registered_with_helpers || ! function_exists( \'wp_register_ability_category\' )' ), 'Native category registration skips when helper registration already succeeded.' );
toolbox_assert( false !== strpos( $abilities, 'wp_has_ability_category' ), 'Native category registration checks for an existing WordPress ability category.' );

$readme = file_get_contents( $root . '/README.md' );
toolbox_assert( false !== strpos( $readme, 'Pixabay and Pexels' ), 'Pixabay and Pexels remain documentation-only reserved image providers.' );
toolbox_assert( false !== strpos( $readme, 'Pinecone and Weaviate' ), 'Pinecone and Weaviate remain documentation-only reserved vector providers.' );

toolbox_assert( false === strpos( $client, 'write_confirmed' ), 'Legacy write_confirmed contract is absent.' );
toolbox_assert( false === strpos( $client, 'confirm_token' ), 'Legacy confirm_token contract is absent.' );

echo "Static contract checks passed.\n";
