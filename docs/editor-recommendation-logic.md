# Editor Recommendation Logic

Status: active design note for editor content-support recommendations.

The editor sidebar exposes focused recommendation intents. Each intent should
do one job, return reviewable candidates, and avoid direct WordPress writes.

## Focused Intents

- `title_suggestions`: hosted AI reads the current title, excerpt, and draft
  text, then returns title candidates. Toolbox parses,
  deduplicates, reranks, and flags weak candidates.
- `summary_suggestions`: hosted AI reads the full draft context and returns
  excerpt candidates. Toolbox strips meta wording, enforces length limits,
  reranks by coverage, and lets the editor copy one candidate into the current
  unsaved excerpt field.
- `category_suggestions`: Toolbox ranks existing WordPress categories by
  current draft token matches and, when supplied by the richer flow, related
  Site Knowledge term evidence. The focused shortcut does not use selected text
  and does not create categories.
- `tag_suggestions`: Toolbox ranks existing WordPress tags by the same rules.
  Review-only new tag gaps may be shown, but Toolbox does not create terms.
- `summary_terms_optimization`: the full workflow that may combine summary,
  taxonomy, Site Knowledge, discoverability evidence, diagnostics, and Core
  handoff preparation.

## Candidate Contract

Focused intents should expose `recommendation_candidate.v1` where practical.
The contract lets batch dry-runs, spreadsheets, and later review queues consume
one common candidate shape while each intent remains independently runnable.

## Ranking Inputs

Local-only ranking can use:

- current draft title, excerpt, selected text, and body text;
- existing WordPress categories and tags;
- exact or token overlap between draft text and term name, slug, or
  description;
- runtime quality gates for length, meta wording, duplication, and unsupported
  claims.

Cloud-assisted ranking can additionally use:

- vector search over historical published articles;
- historical taxonomy usage on semantically related posts;
- similarity scores and source references returned as evidence;
- site-level style and vocabulary patterns.

Cloud vectors should remain evidence and ranking input. They must not become a
second WordPress write authority, taxonomy registry, prompt registry, or audit
store. Accepted writes still go through editor save, Core proposal, or an
explicitly classified local confirmation path.

## New Category Policy

AI may help identify a possible category gap, but new categories are structural
site changes. They should not appear as one-click focused shortcut output. A
future implementation may surface AI-proposed category gaps only in the richer
metadata optimization flow, with duplicate checks against the existing category
tree, historical usage evidence, and Core strong review before any category is
created or assigned.
