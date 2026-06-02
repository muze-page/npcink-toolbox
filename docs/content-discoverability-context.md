# Content Discoverability Context

Status: active first-version contract.

This document records the SEO, AEO, and GEO context surface for future AI
sessions. The feature exists so a WordPress operator can fill in site facts and
content-governance rules in Toolbox, then expose that read-only context through
the WordPress Abilities API for OpenClaw, Agent Gateway, Open API, or other
third-party AI callers.

## Product Shape

Toolbox owns:

- the operator-facing form for site content context;
- storage for non-secret SEO, AEO, and GEO guidance;
- a read-only Abilities API action that returns the context;
- suggestion-only workflow inputs for third-party AI callers.

Toolbox does not own:

- final SEO meta, slug, excerpt, schema, or post writes;
- Core proposal records, approvals, or audit logs;
- OpenClaw, Agent Gateway, Open API, or MCP projection truth;
- a second settings, ability, workflow, approval, or write registry;
- third-party provider secrets in this context payload.

## Storage

Content context is stored separately from provider settings:

```text
magick_ai_toolbox_content_context
```

Do not merge this into:

```text
magick_ai_toolbox_settings
```

The provider settings option may contain API keys and connector endpoints.
Third-party AI context must remain safe to expose through Abilities and must not
include provider keys, request logs, billing details, quotas, or private
credentials.

## Abilities Surface

Current read-only ability:

```text
magick-ai-toolbox/get-content-discoverability-context
```

Scope:

```text
cap.toolbox.context.read
```

Native ability metadata should include:

```text
readonly: true
show_in_rest: true
required_scope: cap.toolbox.context.read
data_classification: public_context
write_posture: suggestion_only
```

The ability returns guidance only. Third-party AI must treat it as context for
suggestions, briefs, and proposal-ready payloads, not as permission to commit
WordPress writes.

## Payload Shape

The ability returns a JSON object shaped like:

```json
{
  "context_type": "content_discoverability",
  "version": 1,
  "write_posture": "suggestion_only",
  "final_write_path": "core_proposal_required",
  "direct_wordpress_write": false,
  "site_positioning": "",
  "target_audience": [],
  "brand_voice": "",
  "keywords": {
    "primary": [],
    "long_tail": [],
    "entities": []
  },
  "claims": {
    "allowed": [],
    "forbidden": []
  },
  "rules": {
    "seo": "",
    "aeo": "",
    "geo": "",
    "allow_faq_generation": true,
    "allow_aeo_summary": true,
    "allow_geo_summary": true,
    "allow_structured_data_suggestions": true
  },
  "proposal_allowed_fields": [
    "seo_title",
    "seo_description",
    "slug",
    "excerpt",
    "faq",
    "answer_summary",
    "geo_summary"
  ],
  "handoff": {
    "consumer": "abilities_or_agent_gateway",
    "final_writes": "core_proposal_required",
    "direct_wordpress_write": false
  }
}
```

## Operator Fields

The first version exposes these admin fields:

- site positioning;
- target audience;
- brand voice;
- primary keywords;
- long-tail keywords;
- entity keywords;
- allowed claims;
- forbidden claims;
- SEO rules;
- AEO rules;
- GEO rules;
- FAQ suggestion toggle;
- AEO answer summary toggle;
- GEO summary toggle;
- structured data suggestion toggle;
- proposal fields AI may suggest.

The admin page also shows an ability-preview JSON block so operators and future
AI sessions can see exactly what third-party callers will receive.

## Third-Party AI Usage

Third-party AI should:

1. read `magick-ai-toolbox/get-content-discoverability-context`;
2. combine it with read-only site/post abilities when needed;
3. produce suggestions for the fields listed in `proposal_allowed_fields`;
4. preserve `forbidden_claims` and the site `brand_voice`;
5. hand write-like outcomes to Core proposal flows.

Third-party AI must not:

- mutate this context;
- treat OpenClaw or Agent Gateway as a second context truth source;
- directly write SEO fields, slugs, excerpts, FAQs, schema, media, or posts;
- invent product facts, customer examples, rankings, citations, or guarantees;
- leak connector keys or private credentials into prompts, outputs, proposals,
  logs, REST responses, or docs.

## Future Work

Possible later abilities:

- `magick-ai-toolbox/validate-content-discoverability-context`
- `magick-ai-toolbox/build-content-discoverability-brief`

Do not add an update-context ability for third-party AI in the first version.
If an external update path becomes necessary, it must be governed by Core and
must not bypass local administrator review.
