# Agent Conventions

Use the skill under `.agents/skills/` that matches the work at hand. Begin with `architecture` for cross-layer changes; use `exam`, `submission`, `computer-vision` or `grading` for domain changes. Apply the framework skills for Laravel, Vue, Pest, Tailwind, Wayfinder and Fortify when relevant.

Keep controller, service, action, repository and model responsibilities separate. Protect all teacher-owned resources with authorization. Run relevant tests when changing behavior.

Default vision/grading model: `google/gemini-3.7-flash` via the respective environment variables. Never commit secrets, real student data, uploaded exams or private storage files.
