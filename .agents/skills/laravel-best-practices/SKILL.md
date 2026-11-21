---
name: laravel-best-practices
description: Guides Laravel service, model, validation, job and database changes.
---

# Laravel Best Practices

Use strict request validation, Eloquent relationships, policies and service/action boundaries. Prefer typed method signatures and dependency injection. Use database transactions for groups of writes but keep file/network operations carefully coordinated with error cleanup. Queue expensive vision and grading work. Always add migrations for new persisted state and align model casts/fillables with migrations. Use the test harness and HTTP/queue/storage fakes for external boundaries. Follow established formatting and PHP version in `composer.json` and Docker configurations; the project uses PHP language features not necessarily supported by a system PHP older than the Docker runtime.
