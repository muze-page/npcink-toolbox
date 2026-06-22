# Media ALT/Caption Operator Trial - 2026-06-21

Status: local real-media read-only trial.

## Purpose

This trial validates the current `media_alt_caption_review_set.v1` surface
before any Toolkit extraction work. It checks whether the review set can scan a
real local WordPress media library, return selected and blocked items, and keep
the whole flow review-only.

The trial is not a migration approval. It is one evidence point for the
[Media ALT/Caption Toolkit Validation Plan](media-alt-caption-toolkit-validation-plan.md).

## Trial Command

```bash
composer smoke:media-alt-caption-trial
composer eval:media-alt-caption:export
composer eval:media-alt-caption:judge-cross
composer eval:media-alt-caption:export-batch
composer eval:media-alt-caption:judge-cross-batch
```

The command runs through WP-CLI against the configured local WordPress site. It
uses a local host filter for the hosted site-helper response so the trial can
exercise the existing `/ai/site-helpers` route without requiring Cloud runtime
availability.

`composer eval:media-alt-caption:export` writes the selected
`media_alt_caption_operator_trial.v1` cases to local `build/eval/`.
`composer eval:media-alt-caption:judge-cross` passes that local artifact to
the development-only `magick-ai-eval-lab` task
`media_alt_caption_judge_cross`. The eval-lab output is AI-assisted review
evidence only; it is not final acceptance truth or write authorization.

Use the `*-batch` commands to accelerate sample collection before making an
extraction decision. The batch exporter pages real local media metadata through
the same `/ai/site-helpers` route, keeps each request capped at 10 items, and
aggregates a local `media_alt_caption_operator_trial.v1` artifact for
eval-lab. This is not a product batch workflow and does not create a queue,
Cloud run, Core proposal, execution, or media metadata write.

For long provider-backed evals, keep `MEDIA_ALT_CAPTION_JUDGE_RESUME=1` and
`MEDIA_ALT_CAPTION_CHECKPOINT_EVERY=1`. The batch judge command reuses an
existing `build/eval/media-alt-caption-batch-cases.json` file by default so
the input fingerprint remains stable across retries; set
`MEDIA_ALT_CAPTION_FORCE_EXPORT=1` only when the sampled cases should be
refreshed. If a 36+ case run is interrupted, rerun the same command and
eval-lab will skip completed matching cases from the same input fingerprint.
Use `MEDIA_ALT_CAPTION_JUDGE_OFFSET` only when you intentionally split a run
into smaller windows. Use `MEDIA_ALT_CAPTION_JUDGE_OUTPUT_JSON`,
`MEDIA_ALT_CAPTION_JUDGE_OUTPUT_MD`, and `MEDIA_ALT_CAPTION_JUDGE_OUTPUT_CSV`
when a full run should write to dedicated report paths.

## Boundary Checked

- real image attachments only; no generated fixture media;
- `media_library_metadata_only_no_pixel_vision`;
- `suggestion_only`;
- no direct media metadata writes;
- no proposal creation;
- no execution creation;
- no media derivative run;
- every selected item requires human visual review;
- batch eval keeps `MEDIA_ALT_CAPTION_PAGE_SIZE` capped at 10 per request;
- attachment title, caption, description, ALT, attached file, metadata hash,
  URL, and modified timestamp remain unchanged.

## Local Trial Result

Command result: pass.

Local WordPress sample:

| Metric | Result |
| --- | --- |
| Scanned image attachments | 10 |
| Eligible items | 2 |
| Selected items | 2 |
| Blocked items | 8 |
| Max items | 10 |
| Selected reason counts | `missing_caption=2` |
| Blocked reason counts | `metadata_complete_for_p0=8` |

Attachment ids checked:

- `283886`
- `283887`
- `283888`
- `283934`
- `283869`
- `284060`
- `283844`
- `8053`
- `7786`
- `7774`

Evidence:

- `/ai/site-helpers` returned `media_alt_caption_review_set.v1`.
- The trial used a local host filter instead of requiring Cloud runtime
  availability.
- `direct_wordpress_write=false`.
- `proposal_created=false`.
- `execution_created=false`.
- `media_derivative_run_created=false`.
- Every selected item required human visual review.
- All attachment metadata snapshots were unchanged before and after the REST
  call.

## AI-Assisted Review Result

Command result: pass.

Eval-lab task: `media_alt_caption_judge_cross`.

Provider-backed profiles:

- `gpt55`
- `grok43`
- `deepseek`

Cross-judge result:

| Metric | Result |
| --- | --- |
| Input contract | `media_alt_caption_operator_trial.v1` |
| Output contract | `media_alt_caption_ai_judge_cross.v1` |
| Cases reviewed | 36 |
| Requested/completed | `36/36` |
| Source fingerprint | `1215c016ca060e7f4fc6b75965d7806e2623eef1` |
| Accepted | 5 |
| Edited | 3 |
| Rejected | 27 |
| Misleading | 1 |
| Partial | `false` |

Profile reliability:

| Profile | Successful judgments | Provider failures |
| --- | ---: | ---: |
| `gpt55` | 36 | 0 |
| `grok43` | 24 | 12 |
| `deepseek` | 36 | 0 |

Top flags:

| Flag | Cases |
| --- | ---: |
| `needs_human_visual_check` | 36 |
| `caption_redundant` | 32 |
| `too_generic` | 32 |
| `metadata_insufficient` | 29 |
| `filename_like` | 24 |
| `provider_error` | 12 |
| `unsupported_visual_claim` | 2 |

Attachment-level result:

| Attachment | Outcome | Average score | Passes | Flags |
| ---: | --- | ---: | ---: | --- |
| `8053` | `accepted` | `0.640` | `1/3` | `caption_redundant`, `too_generic`, `needs_human_visual_check`, `metadata_insufficient` |
| `7774` | `accepted` | `0.577` | `2/3` | `needs_human_visual_check`, `provider_error` |
| `7598` | `edited` | `0.440` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `provider_error`, `unsupported_visual_claim` |
| `6242` | `rejected` | `0.117` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `provider_error` |
| `6241` | `rejected` | `0.117` | `0/3` | `filename_like`, `too_generic`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check`, `provider_error` |
| `6240` | `rejected` | `0.133` | `0/3` | `metadata_insufficient`, `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `provider_error` |
| `6239` | `rejected` | `0.100` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check`, `provider_error` |
| `1377` | `rejected` | `0.150` | `0/3` | `too_generic`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check`, `provider_error` |
| `6238` | `rejected` | `0.100` | `0/3` | `metadata_insufficient`, `filename_like`, `too_generic`, `caption_redundant`, `needs_human_visual_check`, `provider_error` |
| `6237` | `rejected` | `0.100` | `0/3` | `filename_like`, `too_generic`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check`, `provider_error` |
| `6236` | `rejected` | `0.117` | `0/3` | `metadata_insufficient`, `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `provider_error` |
| `5175` | `rejected` | `0.117` | `0/3` | `metadata_insufficient`, `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `provider_error` |
| `4874` | `accepted` | `0.807` | `2/3` | `metadata_insufficient`, `caption_redundant`, `needs_human_visual_check` |
| `1493` | `rejected` | `0.423` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `1692` | `rejected` | `0.267` | `0/3` | `metadata_insufficient`, `too_generic`, `filename_like`, `needs_human_visual_check` |
| `1691` | `rejected` | `0.133` | `0/3` | `filename_like`, `too_generic`, `metadata_insufficient`, `needs_human_visual_check` |
| `1687` | `rejected` | `0.150` | `0/3` | `filename_like`, `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `1686` | `rejected` | `0.117` | `0/3` | `filename_like`, `too_generic`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check` |
| `1628` | `accepted` | `0.533` | `1/3` | `filename_like`, `caption_redundant`, `too_generic`, `needs_human_visual_check`, `metadata_insufficient` |
| `1027` | `misleading` | `0.250` | `0/3` | `unsupported_visual_claim`, `caption_redundant`, `too_generic`, `filename_like`, `needs_human_visual_check`, `metadata_insufficient` |
| `1022` | `rejected` | `0.200` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check` |
| `1045` | `rejected` | `0.167` | `0/3` | `too_generic`, `metadata_insufficient`, `needs_human_visual_check` |
| `1029` | `rejected` | `0.200` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check` |
| `967` | `rejected` | `0.200` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `1025` | `rejected` | `0.217` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check` |
| `968` | `rejected` | `0.200` | `0/3` | `too_generic`, `filename_like`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `1023` | `rejected` | `0.250` | `0/3` | `caption_redundant`, `filename_like`, `too_generic`, `needs_human_visual_check` |
| `827` | `rejected` | `0.133` | `0/3` | `too_generic`, `caption_redundant`, `metadata_insufficient`, `needs_human_visual_check`, `filename_like` |
| `811` | `rejected` | `0.133` | `0/3` | `metadata_insufficient`, `filename_like`, `too_generic`, `caption_redundant`, `needs_human_visual_check` |
| `807` | `rejected` | `0.133` | `0/3` | `filename_like`, `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `769` | `rejected` | `0.467` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `767` | `edited` | `0.827` | `2/3` | `caption_redundant`, `needs_human_visual_check` |
| `766` | `edited` | `0.507` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |
| `765` | `rejected` | `0.417` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `filename_like` |
| `764` | `rejected` | `0.483` | `0/3` | `too_generic`, `caption_redundant`, `needs_human_visual_check`, `metadata_insufficient` |

Interpretation:

- The selected candidates still did not create direct writes, proposals,
  executions, or media derivative runs.
- The 36-case run found one misleading case where generated text contradicted
  existing title/file evidence (`Horizontal` versus `Vertical`), so the review
  set must keep visual confirmation and conflict checks before any handoff.
- Most rejected cases were not provider-quality failures alone: common issues
  were duplicate captions, title/file-name reuse, metadata insufficiency, and
  generic text that did not describe actual visual content.
- `grok43` had 12 provider failures during the run; the result is still useful
  because `gpt55` and `deepseek` completed all 36 cases, but this profile
  should not be treated as a stable release gate until provider reliability is
  improved.
- The useful product direction is not direct application. It is a guarded
  review queue that filters duplicate/file-name-like candidates, marks
  conflict risk, and keeps every suggested ALT/caption behind human visual
  confirmation.

## Follow-Up Decision

Do not move the implementation to `npcink-abilities-toolkit` yet. The sample
size is now sufficient to expose the shape of the problem, and the dominant
problem is candidate quality rather than lack of volume: 28 of 36 cases were
`rejected` or `misleading`.

Before any extraction approval, tighten the Toolbox candidate filter and rerun
the same 36-case gate:

- block caption candidates that only duplicate existing title, ALT, or caption;
- block ALT candidates that are URLs, source attribution, camera defaults, or
  file-name-like strings;
- mark metadata conflicts as `misleading_risk` before they reach the operator;
- keep all accepted/edited candidates as `human_review_required=true`;
- keep the artifact `suggestion_only` with Core handoff only after operator
  review, never direct media metadata writes.

The current implementation should stay in Toolbox as a product-surface review
gate until a follow-up 36-case run shows materially lower rejected/misleading
rates and a real operator review confirms accepted/edited candidates are useful
outside the Toolbox screen.
