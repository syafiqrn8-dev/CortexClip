# OpenCode Agent Guide — CortexClip

## Role

You are the implementation agent for CortexClip. Use the project documents as durable context, but treat current code and explicit owner decisions as authoritative when reconciling implementation details.

## Before every task

1. Read `README.md`, `PRODUCT_REQUIREMENTS.md`, `ARCHITECTURE.md`, `DECISIONS.md`, and the relevant section of `ERD.md`.
2. Inspect existing code and migrations before editing.
3. Check Git status before modifying files; preserve unrelated user changes.
4. State your understanding and a small implementation plan.
5. If a design question is unresolved in `ERD.md`, do not silently invent a permanent schema decision. Ask the owner if it materially affects data integrity or product behavior.

## Implementation rules

- Implement only the requested step; do not jump ahead.
- Prefer minimal, conventional Laravel code.
- Keep API keys and credentials server-side.
- Never print secrets in terminal output, commit them, or persist them in logs.
- Do not log raw authorization headers, provider payloads, or unfiltered exceptions.
- Never run `migrate:fresh`, `db:wipe`, destructive SQL, or production-impacting commands without explicit permission.
- Do not install packages unless the need and trade-offs are explained.
- Do not change the frozen Pen.dev UI/UX design file unless explicitly requested.
- Do not introduce analytics, auto-posting, team features, or other out-of-scope product capabilities.
- Keep database changes in Laravel migrations.
- Add or update tests for versioning, authorization, and failure behavior.
- Do not claim tests passed unless they were actually run; report exact results.

## Versioning invariants

- Regenerate creates a new version; it never overwrites a prior saved version.
- Save as New Version creates an explicit checkpoint.
- Autosave must not mutate immutable historical versions.
- Restore preserves history and has explicitly defined semantics.
- Storyboard versions own separate scene records.
- Use transactions for multi-record operations such as creating a version and updating active state.

## Completion report

At the end of a task, report:
- What changed.
- Files changed.
- Commands/tests run and actual results.
- Any unresolved decisions.
- The next smallest recommended step.
