---
name: pest-testing
description: Guides Pest tests for domain services and integration boundaries.
---

# Pest Testing

Write focused Pest feature tests for user-visible behavior and unit tests for isolated contracts. Use factories, authorized test users, `Queue::fake()`, `Storage::fake('local')` and `Http::fake()` to avoid real OpenRouter calls and writes. Prefer behavior-based assertions to implementation-dependent assertions. Cover ownership, malformed uploads, answer extraction, class optionality, grading, submission metadata preservation and request payload validation. Keep synthetic names and synthetic uploads only; never add scanned student work to fixtures.
