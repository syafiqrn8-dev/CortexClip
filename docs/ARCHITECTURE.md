# CortexClip — Technical Architecture

**Status:** Proposed baseline  
**Last updated:** 2026-10-10

## 1. Stack

- Backend: Laravel 13, PHP 8.3.
- Client: Flutter, Android first.
- API: REST JSON, `/api/v1`.
- Database: PostgreSQL.
- Development media storage: Laravel local disk.
- AI integration: provider abstraction on the backend.
- Later/background processing: Laravel queues and FFmpeg when required.

## 2. High-level boundaries

```text
Flutter app
    |
    | HTTPS / JSON
    v
Laravel API (/api/v1)
    |
    +-- Authentication / authorization
    +-- Project and workflow services
    +-- AI provider abstraction
    +-- Media metadata and storage abstraction
    +-- Export jobs / queue workers (when implemented)
    |
    +--> PostgreSQL (structured metadata)
    +--> Local media storage initially; object storage may be added later
    +--> External AI providers (server-to-server only)
```

## 3. Security requirements

- Flutter must never contain provider API keys.
- Provider credentials belong in server environment configuration or a deployment secret manager.
- `.env` must remain untracked.
- Use authorization policies/scoped queries so users can only access their own projects and associated data.
- Validate input at API boundaries.
- Sanitize errors before storing or returning them.
- Do not log authorization headers, credentials, or raw provider payloads by default.
- Avoid returning local filesystem paths directly to clients when a controlled media URL or API route is appropriate.
- Do not add a dependency or service without explaining its purpose and maintenance trade-off.

## 4. API conventions

- Prefix routes with `/api/v1`.
- Return JSON for API routes.
- Use consistent validation and error response shapes.
- Do not expose internal exception traces in production responses.
- Keep API resources/DTOs separate from internal model details where useful; avoid unnecessary abstraction for trivial endpoints.

## 5. Database conventions

- Use Laravel migrations for schema changes.
- Use foreign keys and indexes for relationships and common lookups.
- Add unique constraints where domain rules require them, such as version number per parent project.
- Use transactions when creating a version and updating the active-version pointer.
- Prefer explicit statuses with validated allowed values.
- Review the existing Laravel migrations and models before creating or altering files.
- Never run `migrate:fresh`, `db:wipe`, or other destructive commands without explicit approval.

## 6. Versioning approach

- Each script version is a separate immutable historical record once created.
- Each storyboard version is a separate record; its scenes belong to that storyboard version.
- Autosave updates the current editable draft according to the final versioning implementation.
- **Save as New Version**, regeneration, and restore must have clearly defined semantics.
- Restoring should point the project at a prior version or create a new restore checkpoint; it must not destroy history.
- Keep active-version references consistent with project ownership. Consider circular foreign-key complexity before choosing the exact schema.
- Do not build a keystroke-by-keystroke version history.

## 7. AI provider abstraction

Use a backend-owned interface/service so business logic does not depend directly on one provider SDK. Store provider and model identifiers with each generation. Keep credentials out of model fields, logs, API responses, and exception messages.

## 8. Media abstraction

Use Laravel's filesystem/storage abstraction. PostgreSQL stores metadata, disk name, and relative storage key. Media files themselves are not stored as PostgreSQL binary values. Keep private assets private by default.

## 9. Background work

AI image/audio generation and video export may be long-running. Start with a simple implementation where reasonable, but introduce queue jobs for work that must not block API requests. Queue configuration and FFmpeg should be implemented only when the corresponding workflow is reached.

## 10. Testing

- Test ownership/authorization for all project-scoped resources.
- Test version creation, restore, and active-version consistency.
- Test validation and failure paths for AI jobs.
- Test that secrets are not persisted in generation records or logs.
- Test media access controls.
- Test migration behavior against the configured PostgreSQL database.
