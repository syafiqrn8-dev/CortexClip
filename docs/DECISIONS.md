# CortexClip — Architecture & Product Decisions (ADR Log)

**Last updated:** 2026-10-10  
This file records decisions explicitly agreed with the project owner. New decisions should be added rather than silently rewriting history.

## Accepted decisions

### D-001 — Android first
- **Decision:** Flutter client targets Android first; iOS is planned later.
- **Status:** Accepted.

### D-002 — Backend and database
- **Decision:** Laravel REST JSON backend with `/api/v1`; PostgreSQL relational database.
- **Status:** Accepted.

### D-003 — Script version history
- **Decision:** Preserve previous script versions; users can browse and restore them.
- **Status:** Accepted.
- **Constraint:** Restoring must not delete prior versions.

### D-004 — Storyboard version history
- **Decision:** Preserve previous storyboard versions; users can browse and restore them.
- **Status:** Accepted.
- **Constraint:** A new storyboard version gets its own scene records so edits do not mutate scenes from earlier versions.

### D-005 — Media storage
- **Decision:** Store media files outside PostgreSQL; store metadata and storage references in PostgreSQL.
- **Status:** Accepted.
- **Initial implementation:** Local storage during development; evaluate object storage later.

### D-006 — AI generation history
- **Decision:** Track AI tasks, provider/model, status, error information, and available usage/timing metrics.
- **Status:** Accepted.
- **Security:** Never store API keys, credentials, authentication headers, or sensitive raw payloads in generation history/logs.

### D-007 — Editing and version checkpoints
- **Decision:** Autosave plus a **Save as New Version** action.
- **Status:** Accepted.
- **Constraint:** Do not create a historical version for every keystroke. Regeneration creates a new version.

### D-008 — One active brief per project in MVP
- **Decision:** A project has one current brief that can be edited.
- **Status:** Accepted.
- **Constraint:** Brief version history is not required for MVP.

### D-009 — Project deletion
- **Decision:** Users can delete projects; use soft deletion initially.
- **Status:** Accepted.
- **Constraint:** Soft deletion does not immediately delete media files; define cleanup/retention later.

## Recommendations, not yet approved as final implementation details

- Use `scene_media` join table to support multiple assets per scene and asset reuse.
- Store subtitle segments per scene; timing semantics still need confirmation.
- Track export jobs separately so rendering can run asynchronously later.
- Keep provider integration behind a backend abstraction.
- Keep root `AGENTS.md` and `CLAUDE.md` local-only if they are intentionally ignored; commit durable project context in `docs/`.

## Change process

When changing an accepted decision:
1. Explain the reason and trade-offs.
2. Ask for approval if it changes product behavior, security, data retention, or architecture materially.
3. Add a new decision entry with date and rationale.
4. Update related docs consistently.
