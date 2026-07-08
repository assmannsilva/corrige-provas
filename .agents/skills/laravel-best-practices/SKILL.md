---
name: laravel-best-practices
description: Laravel domain layering, persistence, validation and background processing.
---

# Laravel Best Practices

The application targets the PHP and Laravel versions declared in `composer.json` and the PHP Dockerfile. Check those files before using framework or language features.

## HTTP and domain boundaries

- Controllers authorize access, validate requests and delegate to services or focused actions.
- Services coordinate domain behavior and transactions. Repositories encapsulate recurring persistence queries.
- Keep models responsible for relationships, casts, fillable attributes and simple domain state.
- Treat uploaded files and external HTTP responses as untrusted, even when coming from another service.
- Enforce exam and submission ownership through policies on every relevant endpoint.

## Persistence and jobs

Add schema migrations for persistent changes; update casts, factories and tests with them. Group related database writes in transactions, but do not keep transactions open around long-running network calls. Clean up stored files if associated persistence fails.

Use queued jobs for computer vision and grading. Keep jobs retryable, preserve processing states and record enough failure details for diagnosis without logging credentials or student work. Ensure queue timeout and reservation settings are coordinated.

## Testing and tooling

Use Pest with `Storage::fake`, `Queue::fake` and `Http::fake` where appropriate. Cover failure paths, unauthorized requests and business state transitions. Run the PHP test suite, PHPStan and Pint checks before merging changes.
