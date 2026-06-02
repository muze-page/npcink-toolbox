# Magick AI Toolbox Boundary

Magick AI Toolbox owns product-facing tools and fixed-flow buttons.

Owned here:

- external research actions;
- image-source candidate actions;
- vector search actions;
- query embedding actions for vector search;
- fixed-flow buttons that return planning artifacts;
- operator-facing admin UI for the toolbox.

Not owned here:

- Core governance truth;
- final WordPress write approval;
- reusable first-party WordPress ability definitions already owned by
  `magick-ai-abilities`;
- workflow runtime, queues, or MCP control-plane state;
- long-term provider billing, quota, and request log ownership.
- content indexing jobs, re-indexing, and vector collection lifecycle in the
  current stage.

First-version write posture:

1. Run research, image-source, or vector-search actions.
2. Return suggestions and handoff notes.
3. Use WordPress abilities and Core proposals for final WordPress writes.

## Connector Boundaries

### Tavily

Tavily owns external web search results and source snippets.

Toolbox may store a Tavily API key and submit bounded search requests. Toolbox
must not treat Tavily results as verified truth; results are source candidates
for operator review.

### Unsplash

Unsplash owns photo search and photo download tracking.

Toolbox may search and display image candidates. Toolbox must preserve
photographer attribution, Unsplash source metadata, and `download_location` for
future import flows. Toolbox must not describe this as image generation.

### Qdrant

Qdrant owns vector collection query storage.

Toolbox may query a configured collection with a text query, supplied vector
JSON payload, or full Qdrant query object.

### SiliconFlow And Jina

SiliconFlow and Jina own text-to-vector embedding generation for the first
Toolbox vector-search path.

Toolbox may send a bounded query string to the configured embedding provider
and use the returned embedding only to query the configured vector database.
Toolbox does not own WordPress indexing, re-index jobs, stale index detection,
or vector collection lifecycle management.

Jina Reader and Jina Reranker are reserved for future workflow-level source
extraction and candidate reranking. They are not part of the first runtime
surface.
