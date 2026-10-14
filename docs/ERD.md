# CortexClip — Entity Relationship Diagram (ERD)

**Status:** Draft proposal — do not create custom migrations until this document is reviewed and approved  
**Last updated:** 2026-10-10

## 1. Scope

This ERD draft covers the MVP workflow: project, brief, script versions, storyboard versions/scenes, media, subtitles, AI task history, and export jobs.

The existing Laravel `users` table should be inspected before modifying it. The exact schema must be reconciled with current migrations and the versioning design before implementation.

## 2. Proposed logical relationships

```text
users 1 ─── N projects
projects 1 ─── 1 content_briefs (one current brief for MVP)
projects 1 ─── N scripts (each row is a script version)
projects 1 ─── N storyboards (each row is a storyboard version)
storyboards 1 ─── N scenes
projects 1 ─── N media_assets
scenes N ─── M media_assets (via scene_media)
scenes 1 ─── N subtitles
projects 1 ─── N ai_generations
projects 1 ─── N export_jobs
```

A project should be able to identify its active script and active storyboard. The exact implementation of these pointers must be selected carefully to avoid invalid cross-project references and unnecessary circular foreign-key complications.

## 3. Proposed entities

### `projects`
Candidate fields:
- `id`
- `user_id` (FK to users)
- `title`
- `platform`
- `aspect_ratio`
- `target_duration_seconds`
- `status`
- `current_stage`
- active script/storyboard reference, exact design TBD
- timestamps
- `deleted_at` for soft delete

### `content_briefs`
One current brief per project in the MVP:
- `id`
- `project_id` (unique FK)
- `topic`
- `target_audience` (nullable)
- `content_goal` (nullable)
- `tone` (nullable)
- `additional_instructions` (nullable)
- timestamps

Brief history/versioning is out of MVP scope unless explicitly added later.

### `scripts`
Each row represents a saved script version:
- `id`
- `project_id` (FK)
- `version_number`
- `content`
- `generation_id` (nullable FK to `ai_generations`, if created)
- `created_by_type` (e.g. `ai`, `user`, `restore`; controlled values)
- timestamps

Constraints to consider:
- unique (`project_id`, `version_number`)
- index (`project_id`, `created_at`)
- decide how editable autosave draft is represented without changing immutable history unintentionally.

### `storyboards`
Each row represents a storyboard version:
- `id`
- `project_id` (FK)
- `version_number`
- `generation_id` (nullable FK to `ai_generations`)
- `created_by_type`
- timestamps

Constraints to consider:
- unique (`project_id`, `version_number`)

### `scenes`
Scenes belong to one storyboard version:
- `id`
- `storyboard_id` (FK)
- `scene_number`
- `visual_description`
- `script_text` (nullable)
- `duration_seconds` (nullable)
- timestamps

Constraint to consider: unique (`storyboard_id`, `scene_number`).

Creating a new storyboard version creates its own scene records; editing new-version scenes must not mutate scenes in older versions.

### `media_assets`
Stores metadata and relative storage references, not file bytes:
- `id`
- `project_id` (FK)
- `media_type` (e.g. `image`, `audio`, `video`)
- `storage_disk`
- `file_path` / storage key
- `mime_type`
- `file_size_bytes`
- `duration_seconds` (nullable)
- `status`
- timestamps
- `deleted_at` if media lifecycle requires soft deletion

### `scene_media`
Join table connecting scenes and assets:
- `id` or composite key (choose consistently with Laravel conventions)
- `scene_id` (FK)
- `media_asset_id` (FK)
- `role` (e.g. `visual`, `voiceover`, `sound_effect`)
- `sort_order`
- timestamps

Add uniqueness only if it matches the chosen semantics; one asset may be reusable in multiple scenes.

### `subtitles`
Proposed initial model: subtitle segments are attached to scenes:
- `id`
- `scene_id` (FK)
- `start_time_ms`
- `end_time_ms`
- `text`
- timestamps

Confirm whether times are scene-relative or project-timeline-relative before implementation. If subtitles can span scene boundaries, this model will need revision.

### `ai_generations`
Tracks the lifecycle/metadata of an AI task:
- `id`
- `project_id` (FK)
- `task_type`
- `provider`
- `model`
- `status` (e.g. `pending`, `processing`, `completed`, `failed`)
- `input_tokens` (nullable)
- `output_tokens` (nullable)
- `duration_ms` (nullable)
- `error_code` (nullable)
- sanitized `error_message` (nullable, optional)
- timestamps

Do not store API keys, auth headers, passwords, raw exception traces, or raw provider payloads here. If prompts/responses must be retained later, treat that as a separate product/privacy decision.

### `export_jobs`
Tracks video export work:
- `id`
- `project_id` (FK)
- `status`
- `output_storage_disk` (nullable)
- `output_path` (nullable)
- `format`
- `resolution` (nullable)
- `error_code` (nullable)
- `started_at` (nullable)
- `completed_at` (nullable)
- timestamps

## 4. Important unresolved schema decisions

Resolve these before migrations:
1. **Autosave and immutable history:** Decide whether scripts/storyboards need separate editable draft records plus immutable version records, or whether a current version row may be edited until explicitly checkpointed. Do not assume immutable history and unrestricted autosave on the same row are compatible.
2. **Active version pointer:** Decide whether `projects` stores `active_script_id` and `active_storyboard_id`, or whether active status is represented by a separate relation/column design. Enforce that the active version belongs to the same project.
3. **Restore semantics:** Decide whether Restore simply switches the active pointer to an existing version or creates a new version copied from the restored content. Preserve all prior history either way.
4. **Subtitle timeline:** Decide whether timing is relative to a scene or to the full project timeline.
5. **Voice settings:** For MVP, store simple settings in a project-level JSON/configuration field only if the shape is small and stable; otherwise design a dedicated table when voice requirements are clarified.
6. **Generation association:** Decide whether `generation_id` foreign keys on outputs are sufficient, or whether a generic output association is needed for image/audio tasks. Avoid polymorphic complexity without a concrete need.
7. **Soft deletion and files:** Define project soft-delete behavior and eventual media cleanup policy. Soft-deleting a project must not immediately delete its underlying files.

## 5. Versioning invariants

- Never overwrite a previously saved historical version during regenerate.
- Version numbers are unique within their parent project.
- Storyboard scenes belong to a particular storyboard version.
- A restored version remains available in history.
- Autosave must not silently mutate an immutable historical version.
- Use transactions when creating a new version and changing the active version.
