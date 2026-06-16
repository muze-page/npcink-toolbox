# ADR-005: Use WP-Cron Local Preview And Cloud Batch Runtime For Nightly Automation

## Status

Accepted

## Date

2026-06-16

## Context

Nightly Site Inspection needs an automation path that can grow from local
operator preview into reliable Pro execution without turning Toolbox into a
workflow runtime. The current plugin already has a bounded Basic WP-Cron
dry-run preview and a Pro Cloud Runtime bridge for Cloud Batch runs.

The team does not want plugin-side Action Scheduler for the current Basic or
Pro path. The added local substrate would create extra queue, claim, retry,
table, and recovery concepts inside the WordPress plugin while Cloud already
has the approved runtime stack for queue-backed execution.

## Decision

Use this split for the current Nightly Site Inspection automation direction:

- Basic/local fallback uses WP-Cron only for disabled-by-default local dry-run
  preview generation.
- Pro uses Cloud Batch Runtime for reliable run/action state, queue-backed
  worker execution, retry, dead-letter handling, entitlement, usage metering,
  quota enforcement, result retention, and observability detail.
- WordPress remains the local control plane and may submit bounded batch intent
  to Cloud through the Cloud Addon runtime seam.
- Cloud may orchestrate accepted runs, but Cloud must not become schedule truth
  for WordPress. Any future scheduled Cloud submit must originate from local
  settings or a local trigger contract and must stop when local controls disable
  it.
- Toolbox stores only bounded local settings, the Basic latest-preview option,
  and review/display state. It must not create a server-side run-history table,
  job table, lease store, retry processor, dead-letter processor, or local
  Cloud execution truth.
- Review outputs may point to Core, but Core remains the proposal, approval,
  preflight, audit, and final WordPress write path. Abilities remain the final
  WordPress callback layer.

## Alternatives Considered

### Plugin-side Action Scheduler

Rejected for the current Basic and Pro path.

Action Scheduler is useful for plugin-local batches, but the current product
split does not need a second local queue. It would add local claim/retry/recovery
state, custom tables, and support burden while duplicating responsibilities
assigned to Cloud Batch Runtime.

### Cloud autonomous scheduler truth

Rejected for the current phase.

Cloud may execute accepted runs and keep runtime detail, but it must not become
the independent schedule owner for a WordPress site. Local controls must remain
the authority for whether Nightly Site Inspection is enabled and what bounded
snapshot is submitted.

### Toolbox-owned runtime queue

Rejected.

Toolbox is the operator surface and release shell for the isolated
`npcink-local-automation-runtime` module. It must not own a hidden workflow
state machine, job queue, approval path, or WordPress write executor.

## Consequences

- Current implementation should not add Action Scheduler integration.
- WP-Cron remains acceptable only as the local fallback preview trigger or a
  future bounded local submit trigger.
- Cloud Batch Runtime is the commercial reliability path for Pro runs.
- Cloud remains runtime/detail only, not a second WordPress control plane.
- Static and smoke tests should fail if production code introduces Action
  Scheduler, runtime custom tables, local job history, local retry/dead-letter
  processors, Cloud scheduler truth, automatic Core proposals, or WordPress
  writes in the Nightly Inspection path.
