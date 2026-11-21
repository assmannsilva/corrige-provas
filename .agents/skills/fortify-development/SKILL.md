---
name: fortify-development
description: Guides Laravel Fortify authentication and security work.
---

# Fortify Development

Use Laravel Fortify's existing service provider and actions for login, logout, password reset and two-factor authentication. Keep validation in Fortify actions, apply Laravel authorization policies to teacher-owned resources, and avoid duplicating login business logic in Vue components. Do not log authentication secrets or token values. Use the existing Pest feature tests under `tests/Feature/Auth` and `tests/Feature/Settings` when changing authentication or security settings.
