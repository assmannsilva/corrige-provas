# Agent Conventions

Consult the appropriate skill in `.agents/skills/` before changing a module. Start with `architecture` for cross-layer changes. The domain-specific skills are `exam`, `submission`, `computer-vision` and `grading`; the remaining skills cover framework conventions.

Follow the project's controller, service, action, repository and model boundaries. Validate input at HTTP boundaries, enforce authorization on teacher-owned resources, and keep slow processing in queued jobs. Update corresponding tests and documentation when behavior changes.

Do not commit `.env`, credentials, uploaded exams, database files or private storage artifacts.
