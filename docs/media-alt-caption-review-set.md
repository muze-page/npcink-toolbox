# Media ALT/Caption Review Set

Status: P0 review-only contract.

## Purpose

The media ALT/caption review set turns the existing **AI Site Helpers -> Media
ALT suggestions** flow into a bounded operator artifact. It helps an operator
find attachments with missing or weak media metadata and review possible ALT or
caption text before any governed write path exists.

This stage is intentionally not a media metadata writer.

## Contract

The local response artifact is `media_alt_caption_review_set.v1`.

It is returned inside the existing `/ai/site-helpers` response when the intent
is `media_alt_suggestions`.

Required posture:

- `write_posture`: `suggestion_only`;
- `final_write_path`: `core_proposal_required`;
- `direct_wordpress_write`: `false`;
- `proposal_created`: `false`;
- `execution_created`: `false`;
- `source_policy`: `media_library_metadata_only_no_pixel_vision`.

Required operational fields:

- `eligibility_summary`;
- `selected_items[]`;
- `blocked_items[]`;
- `operator_next_action`;
- `retryable`;
- `retry_guidance`;
- per-item `status`, `review_reasons`, and `result_ref`.

## Source Boundary

The review set uses a bounded sample of WordPress media-library metadata:

- attachment id;
- title;
- caption;
- description excerpt;
- current ALT;
- filename;
- MIME type;
- thumbnail URL;
- attachment URL.

It does not inspect image pixels. Every selected item has
`needs_human_visual_check: true`, and operators must visually confirm ALT and
caption suggestions before any later handoff.

## Current P0 Behavior

P0 selects image attachments when:

- ALT is missing;
- ALT appears weak or filename-like;
- caption is missing;
- title appears filename-like.

The response defaults to a small review set and caps local selection at 10
items. Items outside the current selection, items with missing attachment ids,
or items already complete for this P0 are reported as blocked items with a
reason.

The admin UI renders the review set as:

- eligibility and blocked counts;
- source policy and contract version;
- selected item rows with ALT candidates and caption candidate;
- blocked item details;
- an explicit "No media metadata was changed" notice.

## Future Apply Path

Applying accepted ALT/caption changes requires a separate governed path:

1. `npcink-abilities-toolkit` defines the media metadata update ability schema
   and dry-run preview.
2. `npcink-governance-core` accepts the proposal, approval, preflight, and audit
   truth.
3. `magick-ai-adapter` relays the approved action through an allowlisted
   execution profile.
4. WordPress Abilities perform the final write callback.
5. Toolbox productizes that accepted path as a fixed operator button.

Until that path exists, Toolbox must not directly write media ALT, caption,
description, replacement URLs, or attachment file data from this review set.

## Non-Goals

- no custom queue table;
- no background worker;
- no automatic media metadata update;
- no automatic proposal creation;
- no final WordPress write;
- no claim that AI has viewed image pixels;
- no reuse of media derivative replacement execution for metadata writes.

