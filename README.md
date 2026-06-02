# Magick AI Toolbox

Magick AI Toolbox is an operator-facing WordPress plugin for external research,
image candidates, knowledge search, and fixed-flow AI actions.

It is intentionally separate from:

- `magick-ai-core`, which owns governance, proposal records, approval, and audit;
- `magick-ai-abilities`, which owns reusable WordPress Abilities API contracts;
- provider connector plugins, which can later own richer key management,
  provider selection, quota, and request logs.

## First Version

The first version provides:

- a Tools admin page at **Tools -> Magick AI Toolbox**;
- OpenAI provider settings with environment-variable support;
- REST endpoints for web research, image candidates, knowledge search, article
  briefs, and media briefs;
- WordPress Abilities API registrations for the same tool actions;
- static tests and PHP syntax linting.

## Boundary

Toolbox returns suggestions and planning artifacts. It does not directly update
posts, upload media, publish content, or bypass governance. WordPress writes
should continue through WordPress abilities and Core proposal approval.

## REST Routes

All routes require a logged-in user with `manage_options`.

- `GET /wp-json/magick-ai-toolbox/v1/status`
- `POST /wp-json/magick-ai-toolbox/v1/web-research`
- `POST /wp-json/magick-ai-toolbox/v1/image-candidates`
- `POST /wp-json/magick-ai-toolbox/v1/knowledge-search`
- `POST /wp-json/magick-ai-toolbox/v1/flows/article-brief`
- `POST /wp-json/magick-ai-toolbox/v1/flows/media-brief`

## Abilities

When the WordPress Abilities API is available, Toolbox registers:

- `magick-ai-toolbox/web-research`
- `magick-ai-toolbox/generate-image-candidate`
- `magick-ai-toolbox/knowledge-search`
- `magick-ai-toolbox/build-article-brief`
- `magick-ai-toolbox/build-media-brief`

When `magick-ai-abilities` is active, Toolbox uses its public registration
helpers so the tools can be discovered by existing Magick AI consumers.

## Provider Configuration

The plugin reads the OpenAI API key in this order:

1. `MAGICK_AI_TOOLBOX_OPENAI_API_KEY` PHP constant;
2. `OPENAI_API_KEY` environment variable;
3. stored WordPress option from the Toolbox settings page.

The text model, image model, vector store id, and feature toggles are
configurable in wp-admin.

## Development

```bash
composer test:all
```

The current gate runs PHP syntax linting and static contract checks.
