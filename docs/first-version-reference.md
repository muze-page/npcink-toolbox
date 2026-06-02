# First Version Reference

Status: active handoff note for future AI sessions.

This file summarizes the first working shape of `magick-ai-toolbox` after the
provider, Abilities API, settings, lifecycle, and local smoke-test work.

## Product Boundary

Magick AI Toolbox is an operator-facing AI tool plugin. It returns suggestions,
source candidates, image candidates, vector matches, and planning artifacts.

Toolbox must not:

- commit WordPress writes;
- import media or set featured images directly;
- own Core approval, audit, or proposal records;
- own workflow runtime, queue, scheduler, MCP, Agent Gateway, or OpenClaw state;
- own WordPress content indexing, re-indexing, stale-index detection, or vector
  collection lifecycle;
- leak provider keys into logs, REST responses, proposals, docs, prompts, or
  handoff text.

Write-like outcomes must be handed to WordPress abilities and Magick AI Core
proposal approval.

## Providers

Current runtime providers:

| Capability | Provider | Runtime status |
| --- | --- | --- |
| Web research | Tavily | Active default. |
| Image source candidates | Unsplash | Active default; preserve attribution and `download_location`. |
| Text query embedding | SiliconFlow | Active default. |
| Text query embedding | Jina AI | Optional provider. |
| Vector database | Qdrant | Active default. |

Reserved only:

- image source: Pixabay, Pexels;
- vector database: Pinecone, Weaviate;
- workflow enhancement: Jina Reader and Jina Reranker.

Reserved providers are documented only. Do not add runtime adapters without a
new contract.

## Embedding And Qdrant

Default embedding provider: SiliconFlow.

Default model: `BAAI/bge-m3`.

Default dimensions: `1024`.

Recommended Qdrant collection distance: `Cosine`.

The vector search action accepts:

- natural-language `query`;
- supplied vector JSON;
- full Qdrant query object.

When the input is text, Toolbox creates a query embedding through the configured
embedding provider and then queries Qdrant. It checks vector dimensions before
querying Qdrant and returns a dimension mismatch error if the returned or
supplied vector length does not match `embedding_dimensions`.

## Settings And Secrets

The settings page supports stored options plus env/constant fallback.

Secrets:

- `TAVILY_API_KEY` / `MAGICK_AI_TOOLBOX_TAVILY_API_KEY`
- `UNSPLASH_ACCESS_KEY` / `MAGICK_AI_TOOLBOX_UNSPLASH_ACCESS_KEY`
- `QDRANT_API_KEY` / `MAGICK_AI_TOOLBOX_QDRANT_API_KEY`
- `SILICONFLOW_API_KEY` / `MAGICK_AI_TOOLBOX_SILICONFLOW_API_KEY`
- `JINA_API_KEY` / `MAGICK_AI_TOOLBOX_JINA_API_KEY`

Provider raw payloads are excluded by default. Enable
`include_raw_responses` only for debugging.

The first version is single-site global configuration. Do not add multisite or
per-user isolation without a new decision.

## Abilities API

Toolbox ability ids stay under `magick-ai-toolbox/*`:

- `magick-ai-toolbox/web-research`
- `magick-ai-toolbox/search-image-source`
- `magick-ai-toolbox/vector-search`
- `magick-ai-toolbox/build-article-brief`
- `magick-ai-toolbox/build-media-brief`

Stable first-version scopes:

- `cap.toolbox.search`
- `cap.toolbox.image_source`
- `cap.toolbox.vector_search`
- `cap.toolbox.workflow_suggest`

Do not rename these scopes unless Magick AI Core explicitly changes the app-key
scope contract.

## Ability Registration Lifecycle

Do not call `register_with_magick_ai_abilities()` synchronously during plugin
hook setup. That triggers translation too early on modern WordPress.

Current lifecycle:

- helper registration is deferred to `wp_abilities_api_categories_init` with
  priority `1`;
- native category registration skips if helper registration already succeeded;
- native category registration also checks `wp_has_ability_category()` before
  registering `magick-ai-toolbox`;
- native ability registration skips when helper registration already succeeded.

This prevents early textdomain notices and duplicate Toolbox category notices.

## Admin Surface

Preferred menu:

- `Magick AI -> Toolbox`
- `admin.php?page=magick-ai-toolbox`

When no shared Magick AI parent menu exists:

- `Tools -> Magick AI Toolbox`
- `tools.php?page=magick-ai-toolbox`

Submenu position is `45`, after Abilities and before Cloud Addon.

## Local Smoke Environment

Verified local site path:

```bash
/Users/muze/Local Sites/magick-ai/app/public
```

Verified plugin symlink:

```bash
/Users/muze/Local Sites/magick-ai/app/public/wp-content/plugins/magick-ai-toolbox -> /Users/muze/gitee/magick-ai-toolbox
```

Global `wp` may not be installed. The verified fallback is a temporary WP-CLI
phar plus Local PHP and the active Local MySQL socket:

```bash
WP_CLI=/tmp/wp-cli.phar
WP_CLI_PHP="/Users/muze/Library/Application Support/Local/lightning-services/php-8.0.30+0/bin/darwin-arm64/bin/php"
WP_CLI_MYSQL_SOCKET="/Users/muze/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock"
WP_PATH="/Users/muze/Local Sites/magick-ai/app/public"
```

Do not write local admin passwords into repository files.

Useful smoke commands:

```bash
"$WP_CLI_PHP" -d mysqli.default_socket="$WP_CLI_MYSQL_SOCKET" -d pdo_mysql.default_socket="$WP_CLI_MYSQL_SOCKET" "$WP_CLI" --path="$WP_PATH" plugin activate magick-ai-toolbox

"$WP_CLI_PHP" -d mysqli.default_socket="$WP_CLI_MYSQL_SOCKET" -d pdo_mysql.default_socket="$WP_CLI_MYSQL_SOCKET" "$WP_CLI" --path="$WP_PATH" plugin status magick-ai-toolbox

"$WP_CLI_PHP" -d mysqli.default_socket="$WP_CLI_MYSQL_SOCKET" -d pdo_mysql.default_socket="$WP_CLI_MYSQL_SOCKET" "$WP_CLI" --path="$WP_PATH" eval 'wp_set_current_user( 1 ); do_action( "rest_api_init" ); $request = new WP_REST_Request( "GET", "/magick-ai-toolbox/v1/status" ); $response = rest_do_request( $request ); echo "status=" . $response->get_status() . "\n";'
```

Adapter and Abilities smoke commands can use the same variables:

```bash
cd /Users/muze/gitee/magick-ai-abilities
WP_CLI=/tmp/wp-cli.phar WP_CLI_PHP="$WP_CLI_PHP" WP_CLI_ERROR_REPORTING=8191 WP_CLI_MYSQL_SOCKET="$WP_CLI_MYSQL_SOCKET" WP_PATH="$WP_PATH" composer smoke:wp

cd /Users/muze/gitee/magick-ai-adapter
WP_CLI=/tmp/wp-cli.phar WP_CLI_PHP="$WP_CLI_PHP" WP_CLI_ERROR_REPORTING=8191 WP_CLI_MYSQL_SOCKET="$WP_CLI_MYSQL_SOCKET" WP_PATH="$WP_PATH" composer smoke:wp
```

Do not manually re-fire `wp_abilities_api_categories_init` and
`wp_abilities_api_init` after WordPress has already loaded all active plugins;
that can produce duplicate notices from other active plugins unrelated to
Toolbox.

## Verification Gates

Default Toolbox gates:

```bash
composer test:all
composer validate --no-check-publish
git diff --check
```

`composer.json` intentionally omits a Composer `version` field. The plugin
version belongs in the plugin header and `readme.txt`.
