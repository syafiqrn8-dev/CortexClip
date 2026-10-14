# CortexClip — Product Requirements (PRD)

**Status:** MVP scope baseline; update through explicit decisions  
**Last updated:** 2026-10-10

## 1. Product vision

CortexClip is an AI co-creator that helps beginner creators create short-form videos for TikTok, Instagram Reels, and YouTube Shorts with fewer tools and less time.

The product is not intended to remove user control. AI generates suggestions and drafts; the user reviews, edits, accepts, or regenerates them.

## 2. Target platform

- Android first.
- iOS later.
- Flutter client.
- Laravel REST JSON backend, versioned under `/api/v1`.
- PostgreSQL for relational application data.
- Media files stored outside PostgreSQL.

## 3. Primary user journey

`Idea/topic → Brief → Script → Storyboard → Media → Voice → Subtitle → Edit → Preview/export → AI Content Kit`

The storyboard is a required review/edit step before the user proceeds to media generation or selection.

## 4. MVP capabilities

### Core workflow
- Create and manage projects.
- Capture a content brief.
- Generate a script with AI.
- Edit the script manually.
- Regenerate the script with user feedback.
- Preserve script versions and allow restore.
- Generate a storyboard and require review/edit.
- Preserve storyboard versions and allow restore.
- Associate visual/audio assets with scenes.
- Configure basic voice/TTS options.
- Create/edit subtitles.
- Provide a scene-based, simple editor.
- Preview and export a video.
- Generate an AI Content Kit: caption, title, and hashtags.

### Additional MVP helpers
- Inspire Me.
- Hook Generator.
- Content Variations.
- Quality Check checklist.
- Platform export presets.
- AI-generated caption.

## 5. Out of scope for MVP

- Advanced professional video editing.
- Analytics dashboards.
- Auto-posting and scheduling.
- AI avatars.
- Content calendar.
- Team collaboration.
- Monetization features.
- Advanced autonomous agent workflows.

## 6. User control and versioning

- AI output is a proposal, not an irreversible action.
- Script and storyboard history must be preserved.
- Users can inspect previous versions and restore one.
- Restoring a previous version must not delete or overwrite other versions.
- Manual editing uses autosave.
- Users can explicitly use **Save as New Version** to preserve a meaningful checkpoint.
- Regeneration creates a new version rather than overwriting the prior version.
- Do not create a historical version for every keystroke.
- Undo/redo may be limited to recent editor changes in the MVP; it is distinct from version history and restore.

## 7. Media storage

- PostgreSQL stores metadata and storage references, not the binary content of image/audio/video files.
- Start with Laravel local storage during development.
- Keep storage access behind Laravel's storage abstraction so object storage can be adopted later.
- Private media must not be exposed through an uncontrolled public path.
- Plan for temporary files, unused assets, upload limits, and eventual cleanup.

## 8. AI task history and privacy

Record useful task metadata, such as:
- Project and task type.
- Provider and model identifiers.
- Status and timestamps.
- Duration.
- Token usage when available.
- Sanitized error code/details.

Never store API keys, access tokens, database credentials, or authentication headers in generation history or logs. Do not automatically persist full raw provider requests/responses. Any later prompt/response retention feature requires a separate privacy and retention decision.

## 9. Export jobs

Video rendering/export may take time and should be represented as a job with a status, output storage reference, format/resolution, timestamps, and sanitized error information. Queue and FFmpeg implementation can be introduced when the export workflow is implemented.

## 10. Product quality principles

- Clear, beginner-friendly workflow.
- Review and editing before final output.
- Recoverability for important creative work.
- Visible progress and actionable errors.
- Keep MVP implementation small and maintainable.
- Never claim an AI task succeeded until the provider response and persistence steps have succeeded.
