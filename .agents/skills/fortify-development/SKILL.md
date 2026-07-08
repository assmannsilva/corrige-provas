---
name: fortify-development
description: Laravel Fortify authentication, account settings and security conventions.
---

# Fortify Development

## Ownership of authentication

Laravel Fortify owns authentication, password recovery and two-factor flows. Reuse the configured `FortifyServiceProvider` and the actions under `app/Actions/Fortify`; avoid duplicating login logic in controllers or Vue pages.

## Implementation

- Keep password complexity and input rules in the existing validation concerns and request classes.
- Handle login, logout and recovery through Fortify endpoints and their intended guards.
- Store passwords using Laravel hashing and never include credentials or recovery tokens in logs or events.
- Use backend validation errors for user feedback; Vue pages should not be the sole enforcement point.
- Apply policies separately to exams and submissions. Authentication only establishes a user identity; it does not authorize access to another teacher's data.
- Return redirects or Inertia responses consistent with the surrounding settings pages.

## Verification

Run the authentication and security feature suites under `tests/Feature/Auth` and `tests/Feature/Settings`. Check failed credentials, recovery requests, password changes, session invalidation and authorization boundaries whenever these flows change.
