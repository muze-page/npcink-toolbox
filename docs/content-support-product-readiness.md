# Content Support Product Readiness

Status: active current-phase acceptance matrix as of 2026-06-10.

This document records how the current Toolbox product surface supports article
work outside the article body. The default product is not autonomous article
writing. It is a set of fixed editor tools that help a human prepare, review,
enrich, and hand off reviewable changes through the existing WordPress
governance boundary.

## Product Rule

Toolbox may recommend, preview, and package reviewed handoffs. Toolbox must not
approve Core proposals, execute final WordPress writes, create a second media
registry, or become a prompt/model control plane.

Human editors own the article text. Article Assistant remains a fallback
workbench for reviewed local artifacts, not the default editor button that
promises to write the article body.

## Acceptance Matrix

| Product focus | Current implementation | Acceptance state | Boundary |
| --- | --- | --- | --- |
| Writing preparation | Editor Content Support exposes `writing_support` and calls Cloud Site Knowledge through `writing_support_plan`. | Accepted for current phase. It prepares context, angles, gaps, and evidence prompts around the article. | Suggestion-only. It does not generate or insert the article body. |
| Summary, category, and tag recommendations | Editor Content Support exposes summary/category/tag flows, including `summary_terms_optimization`, and can return reviewed metadata apply handoff artifacts. | Accepted for current phase. High-frequency metadata review stays in the editor surface. | New vocabulary and accepted metadata writes stay Core-governed or future strong-local-confirmation only. |
| Internal-link candidates | Editor Content Support exposes `internal_links` over bounded article and related-content context. | Accepted for current phase. The surface returns manual review candidates. | No automatic insertion, no direct block mutation, and no link graph control plane. |
| Image candidates and media optimization | Editor Content Support exposes `image_candidates`; Toolbox admin owns the fixed `media_optimization_v1` Optimize Existing Image flow with media derivative preview and Core proposal handoff. | Accepted for current phase. Crop override controls, preview-only Cloud Checks, and Core media proposal proof are implemented. | Image-source candidates remain candidates; media derivative adoption remains one reviewed Core proposal, not direct media writes. |
| Publish preflight and SEO handoff | Editor Content Support exposes `publish_preflight`, returns `pre_publish_review.v1`, and packages `seo_meta_handoff_preview.v1` for `npcink-abilities-toolkit/set-post-seo-meta`. | Accepted for current phase. Browser validation created a pending Core SEO proposal from the editor, and Core review now surfaces `field_patch` values before raw JSON. | Toolbox creates only a pending proposal. Approval, preflight, audit, and execution authorization stay in Core/Adapter/Abilities. |
| Article body generation | Article Assistant Workbench exists for broad fallback packaging and reviewed local draft artifacts. | Intentionally not the primary product. Do not promote this as a default article generator. | No Cloud article generation, no autonomous writer, no one-click long-form writing promise. |

## Verification Evidence

- `composer test:all`
- `composer smoke:editor-review-artifacts`
- `composer smoke:media-derivative-core`
- Browser check: editor publish preflight created one pending Core SEO proposal
  from `seo_meta_handoff_preview.v1`.
- Browser check: Core proposal detail shows `字段变更`, `seo_title`, and
  `seo_description` in review context before the raw proposal payload.

## Next Gate

Stop expanding the editor surface until the six rows above remain stable in
review. The next useful work should be regression hardening: keep smoke coverage
for real editor-to-Core handoffs and only add new buttons when they reuse the
same fixed ability ids, artifact shapes, and Core-governed write paths.
