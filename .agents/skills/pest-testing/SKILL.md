---
name: pest-testing
description: Pest and PHPUnit testing guidelines for application and integration boundaries.
---

# Pest Testing

## Layout

Keep domain integration tests in `tests/Feature` and focused contract tests in `tests/Unit`. Existing suites cover authentication, settings, exams, question management, PDF generation, submissions and grading. Python processing tests live under `python/tests` and use pytest.

## Test setup

- Prefer factories over direct database fixtures.
- Use synthetic names and exam content; never add real student material to the repository.
- Use `Storage::fake('local')` for file operations and `Queue::fake()` for jobs not under test.
- Use `Http::fake()` for OpenRouter or internal-service boundaries. Tests must not require a real API key.
- Validate final application behavior, including HTTP status, persisted state and dispatched work, rather than only checking calls to implementation details.

## Cases to cover

Exercise ownership, invalid requests, invalid files, exam status transitions, question ordering, multiple upload pages, reprocessing, extraction failure, blank answers, objective grading and teacher score overrides. Verify that optional fields behave correctly and that reruns do not duplicate answer records.

## Commands

Run `php artisan test --compact` for the whole Laravel suite or a targeted `tests/Feature/...` path while developing. Run `python -m pytest python/tests -q` for the vision service. The PHP test environment uses SQLite in memory and needs `pdo_sqlite`.
