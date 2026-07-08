---
name: architecture
description: Defines the application architecture and dependency rules.
---

# Project Architecture

The application is a Laravel application with Vue/Inertia presentation, an internal Python computer-vision service, and asynchronous queue processing.

## Responsibility boundaries

- **Controllers:** authorize access, validate HTTP requests, call services/actions, return Inertia pages or redirects. No business workflow orchestration in controllers.
- **Services:** coordinate business transactions and persistence, including exam management, grading and submissions.
- **Repositories:** isolate storage operations and Eloquent queries where useful.
- **Models:** describe relationships, casts, fillable fields, scopes and simple domain invariants.
- **Actions:** encapsulate focused processing steps, especially computer-vision payload construction, extraction persistence and status transitions.
- **Jobs:** dispatch slow extraction and grading without blocking HTTP requests.
- **Question types and renderers:** keep assessment type behavior extensible and rendering concerns separate.
- **Python:** aligns scanned pages and extracts answer crops.

## Data flow

`HTTP -> Policy -> Controller -> Service/Action -> Repository/Model -> Job -> Python CV -> Grading -> Persistence`.

Use dependency injection and small transactions. Keep external API calls outside database transactions. Validate results from external systems before persistence and expose safe errors to users. Keep teacher-owned exam and submission authorization in policies.

## Workflow conventions

- Implement migrations and domain contracts before services and controllers.
- Keep API and Python schema changes synchronized.
- Prefer targeted Pest tests for changed behavior and fake external network calls.
- Never commit `.env`, databases, scanned student work, local keys, or generated crops.
