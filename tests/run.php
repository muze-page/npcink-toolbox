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
toolbox_assert( false !== strpos( $admin_page, 'magick-ai-toolbox__status-strip' ), 'Admin page shows a compact connector and content-context status strip.' );
toolbox_assert( false !== strpos( $admin_page, 'data-toolbox-tabs' ), 'Admin page separates tools, content context, and connectors into top-level tabs.' );
toolbox_assert( false !== strpos( $admin_page, 'data-toolbox-tab-target="context" aria-selected="true"' ), 'Content Context is the default admin tab.' );
toolbox_assert( false !== strpos( $admin_page, 'data-toolbox-tab-target="tools" aria-selected="false"' ) && false !== strpos( $admin_page, 'Try Tools' ), 'Tool execution is a secondary Try Tools tab.' );
toolbox_assert( false !== strpos( $admin_page, 'data-toolbox-tab-panel="connectors"' ), 'Connector settings are moved out of the default tools view.' );
toolbox_assert( false !== strpos( $admin_page, 'data-toolbox-tools' ) && false !== strpos( $admin_page, 'data-toolbox-tool-panel' ), 'Tool actions use a single active tool workspace instead of a card matrix.' );
toolbox_assert( false !== strpos( $admin_page, 'magick-ai-toolbox__disclosure' ) && false !== strpos( $admin_page, 'Ability preview' ), 'Content context and connector detail use disclosures for lower-frequency details.' );
toolbox_assert( false !== strpos( $admin_page, '<div class="magick-ai-toolbox__result is-empty"' ), 'Tool result panels use structured result containers instead of raw preformatted output.' );
toolbox_assert( false !== strpos( $admin_page, 'contextDrafts' ) && false !== strpos( $admin_page, 'get_ai_blog_context_template' ), 'Content context exposes an editable AI technology blog draft template.' );
toolbox_assert( false !== strpos( $admin_page, 'get_site_content_context_suggestion' ) && false !== strpos( $admin_page, 'get_posts(' ) && false !== strpos( $admin_page, 'get_terms(' ), 'Content context can draft suggestions from current public site content signals.' );
toolbox_assert( false !== strpos( $admin_page, "data-toolbox-context-draft=\"aiBlog\"" ) && false !== strpos( $admin_page, "data-toolbox-context-draft=\"site\"" ), 'Content context includes template and current-site draft buttons.' );
toolbox_assert( false !== strpos( $admin_page, 'SEO fields AI may suggest' ) && false !== strpos( $admin_page, 'AEO fields AI may suggest' ) && false !== strpos( $admin_page, 'GEO fields AI may suggest' ), 'Content context groups proposal fields by SEO, AEO, and GEO.' );
toolbox_assert( false !== strpos( $admin_page, 'Drafts are editable suggestions and do not change posts, media, SEO meta, or provider settings.' ), 'Content context draft copy preserves suggestion-only boundaries.' );
toolbox_assert( false !== strpos( $admin_page, 'JSON_UNESCAPED_UNICODE' ), 'Content context ability preview keeps non-Latin text readable.' );

$admin_js = file_get_contents( $root . '/assets/admin.js' );
toolbox_assert( false !== strpos( $admin_js, 'initTopTabs' ) && false !== strpos( $admin_js, 'initToolSwitcher' ), 'Admin JavaScript initializes section tabs and tool switching.' );
toolbox_assert( false !== strpos( $admin_js, "result.hidden = false" ), 'Tool results stay hidden until a tool returns output.' );
toolbox_assert( false !== strpos( $admin_js, 'renderStructuredResult' ) && false !== strpos( $admin_js, 'renderShell' ), 'Admin JavaScript renders tool results through a summary-first structured renderer.' );
toolbox_assert( false !== strpos( $admin_js, 'createRawDetails' ) && false !== strpos( $admin_js, 'Complete payload' ), 'Complete payload output is moved behind a result disclosure.' );
toolbox_assert( false !== strpos( $admin_js, 'Provider raw response' ), 'Provider raw responses are rendered only as disclosure details.' );
toolbox_assert( false !== strpos( $admin_js, 'Download tracking' ) && false !== strpos( $admin_js, 'Attribution metadata' ), 'Image candidate rendering preserves Unsplash attribution and download tracking metadata.' );
toolbox_assert( false !== strpos( $admin_js, 'Governed handoff' ) && false !== strpos( $admin_js, 'Core proposal required' ), 'Workflow result rendering keeps governed handoff guidance visible.' );
toolbox_assert( false === strpos( $admin_js, 'result.textContent = JSON.stringify(value, null, 2)' ), 'Tool results do not default to raw JSON in the main result surface.' );
toolbox_assert( false !== strpos( $admin_js, 'initContextDrafts' ) && false !== strpos( $admin_js, 'applyContextDraft' ), 'Admin JavaScript can prefill editable content context drafts.' );
toolbox_assert( false !== strpos( $admin_js, 'clearContextForm' ), 'Admin JavaScript can clear the content context form before a new draft.' );

$admin_css = file_get_contents( $root . '/assets/admin.css' );
toolbox_assert( false !== strpos( $admin_css, 'magick-ai-toolbox__result-summary' ), 'Admin CSS styles summary-first result panels.' );
toolbox_assert( false !== strpos( $admin_css, 'magick-ai-toolbox__result-details' ), 'Admin CSS styles collapsed result detail disclosures.' );
toolbox_assert( false !== strpos( $admin_css, 'magick-ai-toolbox__image-thumb' ), 'Admin CSS supports browser image-source previews.' );

$plugin = file_get_contents( $root . '/includes/Plugin.php' );
toolbox_assert( false !== strpos( $plugin, 'register_with_magick_ai_abilities' ) && false !== strpos( $plugin, "'wp_abilities_api_categories_init'" ) && false !== strpos( $plugin, ', 1 );' ), 'Helper ability registration is deferred to the Abilities API category hook.' );
toolbox_assert( false === strpos( $plugin, '$this->abilities->register_with_magick_ai_abilities();' ), 'Helper ability registration is not executed during plugin hook setup.' );

$rest = file_get_contents( $root . '/includes/Rest_Controller.php' );
foreach ( array( '/web-research', '/image-candidates', '/vector-search', '/knowledge-search', '/flows/article-brief', '/flows/media-brief' ) as $route ) {
	toolbox_assert( false !== strpos( $rest, $route ), "REST route {$route} is registered." );
}

$abilities = file_get_contents( $root . '/includes/Abilities.php' );
foreach ( array( 'magick-ai-toolbox/web-research', 'magick-ai-toolbox/search-image-source', 'magick-ai-toolbox/vector-search', 'magick-ai-toolbox/build-article-brief', 'magick-ai-toolbox/build-media-brief', 'magick-ai-toolbox/get-content-discoverability-context' ) as $ability_id ) {
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
toolbox_assert( false === strpos( $client, 'provider_body' ), 'Provider error responses do not expose raw provider bodies.' );
toolbox_assert( false !== strpos( $client, "'write_posture' => 'suggestion_only'" ), 'Article brief handoff stays suggestion-only.' );
toolbox_assert( false !== strpos( $client, 'Create WordPress draft or media proposals through Abilities/Core.' ), 'Article brief handoff points write-like actions to Abilities/Core.' );

$settings = file_get_contents( $root . '/includes/Settings.php' );
toolbox_assert( false !== strpos( $settings, 'BAAI/bge-m3' ), 'SiliconFlow default embedding model is configured.' );
toolbox_assert( false !== strpos( $settings, 'jina-embeddings-v3' ), 'Jina default embedding model is configured.' );
toolbox_assert( false !== strpos( $settings, "'embedding_dimensions'  => 1024" ), 'Default embedding dimensions match BAAI/bge-m3 and Qdrant guidance.' );
toolbox_assert( false !== strpos( $settings, 'SILICONFLOW_API_KEY' ), 'SiliconFlow key can be provided by environment.' );
toolbox_assert( false !== strpos( $settings, 'JINA_API_KEY' ), 'Jina key can be provided by environment.' );
toolbox_assert( false !== strpos( $settings, 'content_context_defaults' ), 'Content context has separate defaults.' );
toolbox_assert( false !== strpos( $settings, 'get_content_context_for_ability' ), 'Content context can be exported for Abilities callers.' );
toolbox_assert( false !== strpos( $settings, "'write_posture'                   => 'suggestion_only'" ), 'Content context is suggestion-only.' );
toolbox_assert( false !== strpos( $settings, "'final_write_path'                => 'core_proposal_required'" ), 'Content context points writes to Core proposals.' );
toolbox_assert( false !== strpos( $settings, "'direct_wordpress_write'          => false" ), 'Content context forbids direct WordPress writes.' );

toolbox_assert( false !== strpos( $rest, 'siliconflow_configured' ), 'Status reports SiliconFlow configuration.' );
toolbox_assert( false !== strpos( $rest, 'jina_configured' ), 'Status reports Jina configuration.' );
toolbox_assert( false !== strpos( $rest, 'embedding_dimensions' ), 'Status reports embedding dimensions.' );
toolbox_assert( false !== strpos( $rest, 'query or vector field' ), 'Vector REST route accepts query or vector input.' );
toolbox_assert( false !== strpos( $rest, 'magick_ai_toolbox_rest_permission' ), 'REST permission can be mediated by a host scope filter.' );

toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.search' ), 'Web ability exposes the stable Toolbox search scope.' );
toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.vector_search' ), 'Vector ability exposes a Toolbox vector scope.' );
toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.workflow_suggest' ), 'Workflow abilities expose the stable Toolbox workflow scope.' );
toolbox_assert( false !== strpos( $abilities, 'cap.toolbox.context.read' ), 'Content context ability exposes a read scope.' );
toolbox_assert( false !== strpos( $abilities, 'public_context' ), 'Content context ability declares public context classification.' );
toolbox_assert( false !== strpos( $abilities, "'provider_execution'       => 'server_side_toolbox'" ), 'Provider-backed abilities declare server-side execution.' );
toolbox_assert( false !== strpos( $abilities, "'provider_secret_exposure' => 'none'" ), 'Abilities declare that provider secrets are not exposed.' );
toolbox_assert( false !== strpos( $abilities, "'final_write_path'         => 'core_proposal_required'" ), 'Abilities point write-like outcomes to Core proposals.' );
toolbox_assert( false !== strpos( $abilities, "'direct_wordpress_write'   => false" ), 'Abilities declare direct WordPress writes disabled.' );
toolbox_assert( false !== strpos( $abilities, 'get_content_discoverability_context' ), 'Content context ability has an execution callback.' );
toolbox_assert( false !== strpos( $abilities, "array( 'query' )" ), 'Vector ability accepts query input for AI callers.' );
toolbox_assert( false !== strpos( $abilities, 'magick_ai_toolbox_ability_permission' ), 'Ability permission can be mediated by a host scope filter.' );
toolbox_assert( false !== strpos( $abilities, '$this->registered_with_helpers || ! function_exists( \'wp_register_ability_category\' )' ), 'Native category registration skips when helper registration already succeeded.' );
toolbox_assert( false !== strpos( $abilities, 'wp_has_ability_category' ), 'Native category registration checks for an existing WordPress ability category.' );

$readme = file_get_contents( $root . '/README.md' );
toolbox_assert( false !== strpos( $readme, 'Pixabay and Pexels' ), 'Pixabay and Pexels remain documentation-only reserved image providers.' );
toolbox_assert( false !== strpos( $readme, 'Pinecone and Weaviate' ), 'Pinecone and Weaviate remain documentation-only reserved vector providers.' );
toolbox_assert( false !== strpos( $readme, 'Connector Ability Exposure' ), 'README links the connector ability exposure contract.' );
toolbox_assert( false !== strpos( $readme, 'Content Discoverability Context' ), 'README links the content context contract.' );
toolbox_assert( false !== strpos( $readme, 'Content Assistant Surface Lessons' ), 'README links the Content Assistant surface lessons contract.' );

$connector_exposure_doc = file_get_contents( $root . '/docs/connector-ability-exposure.md' );
toolbox_assert( false !== $connector_exposure_doc && false !== strpos( $connector_exposure_doc, 'provider_secret_exposure: none' ), 'Connector exposure documentation records secret non-exposure.' );
toolbox_assert( false !== strpos( $connector_exposure_doc, 'server_side_toolbox' ), 'Connector exposure documentation records server-side provider execution.' );
toolbox_assert( false !== strpos( $connector_exposure_doc, 'Do not add `confirm_token`, `write_confirmed`' ), 'Connector exposure documentation blocks direct write confirmation contracts.' );

$content_context_doc = file_get_contents( $root . '/docs/content-discoverability-context.md' );
toolbox_assert( false !== $content_context_doc && false !== strpos( $content_context_doc, 'magick-ai-toolbox/get-content-discoverability-context' ), 'Content context documentation records the ability id.' );
toolbox_assert( false !== strpos( $content_context_doc, 'Do not add an update-context ability' ), 'Content context documentation blocks third-party updates in the first version.' );

$content_assistant_surface_doc = file_get_contents( $root . '/docs/content-assistant-surface-lessons.md' );
toolbox_assert( false !== $content_assistant_surface_doc && false !== strpos( $content_assistant_surface_doc, 'summary -> detail' ), 'Content Assistant surface lessons document records summary-first display discipline.' );
toolbox_assert( false !== strpos( $content_assistant_surface_doc, 'Do Not Absorb' ) && false !== strpos( $content_assistant_surface_doc, 'preview -> confirm apply' ), 'Content Assistant surface lessons document blocks write-flow absorption.' );
toolbox_assert( false !== strpos( $content_assistant_surface_doc, 'Toolbox surfaces. Core governs. WordPress writes through abilities.' ), 'Content Assistant surface lessons document records the Toolbox-specific boundary phrase.' );

toolbox_assert( false === strpos( $client, 'write_confirmed' ), 'Legacy write_confirmed contract is absent.' );
toolbox_assert( false === strpos( $client, 'confirm_token' ), 'Legacy confirm_token contract is absent.' );

$uninstall = file_get_contents( $root . '/uninstall.php' );
toolbox_assert( false !== strpos( $uninstall, 'magick_ai_toolbox_content_context' ), 'Uninstall removes content context option.' );

echo "Static contract checks passed.\n";
