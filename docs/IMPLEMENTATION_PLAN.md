# CortexClip — Incremental Implementation Plan

**Status:** Proposed plan; execute one step at a time  
**Last updated:** 2026-10-10

## Phase 2 — Technical setup and architecture

### Step 2.1 — Environment baseline
- [x] Laravel project created.
- [x] PHP 8.3 and Composer available.
- [x] PostgreSQL installed and accessible.
- [x] Laravel configured for PostgreSQL.
- [x] Initial Laravel migrations have run.
- [ ] Confirm `git status`, inspect `.gitignore`, and prepare initial commit after reviewing staged files.
- [ ] Ensure `.env`, local media, logs, and generated files are excluded from Git.

### Step 2.2 — Documentation and schema design
- [x] Capture product requirements and decisions in Markdown.
- [x] Draft ERD.
- [ ] Resolve the open ERD questions listed in `ERD.md`.
- [ ] Approve final MVP ERD before migrations.

### Step 2.3 — Database implementation
- [ ] Inspect existing Laravel migrations, models, and conventions.
- [ ] Design migration ordering and foreign keys.
- [ ] Add custom migrations incrementally.
- [ ] Run migrations against the configured PostgreSQL database.
- [ ] Verify schema and indexes.
- [ ] Add model relationships and focused tests.

### Step 2.4 — API foundation
- [ ] Confirm authentication strategy before exposing project APIs.
- [ ] Set API response/error conventions.
- [ ] Add authorization and project ownership tests.
- [ ] Implement project CRUD only after schema approval.

### Step 2.5 — AI integration foundation
- [ ] Define backend AI provider interface.
- [ ] Keep provider keys server-side.
- [ ] Add sanitized generation history.
- [ ] Test success and failure paths without logging secrets.

### Step 2.6 — Media foundation
- [ ] Use Laravel Storage abstraction.
- [ ] Add upload validation and private media access.
- [ ] Store only metadata and storage keys in PostgreSQL.
- [ ] Define cleanup behavior before deleting files.

### Step 2.7 — Script/storyboard workflow
- [ ] Implement script generation and editing.
- [ ] Implement autosave and Save as New Version.
- [ ] Implement history and restore.
- [ ] Implement storyboard versions and scene cloning.
- [ ] Test version integrity.

### Step 2.8 — Subtitle/editor/export workflow
- [ ] Resolve subtitle timeline semantics.
- [ ] Implement scene/media associations.
- [ ] Implement export job lifecycle.
- [ ] Add queue/FFmpeg only when the workflow requires it.

## Working protocol

For each step, the coding agent should:
1. Inspect relevant existing files.
2. Summarize intended changes before broad or risky modifications.
3. Implement only the current step.
4. Run relevant tests/format checks.
5. Report changed files, commands, outcomes, and remaining risks.
6. Never perform destructive database operations without explicit approval.
