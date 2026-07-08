---
name: wayfinder-development
description: Typed Laravel route helpers used by the Vue frontend.
---

# Wayfinder Development

Wayfinder generates typed frontend functions from Laravel routes and controllers. Keep route definitions under `routes/web.php` and `routes/settings.php`, and use generated imports in Vue pages and components where applicable.

## Route changes

- Give new endpoints stable names and explicit HTTP verbs.
- Update frontend callers when route parameters or controller signatures change.
- Never treat a generated route type as a substitute for backend request validation or authorization.
- Prefer named route helpers to literal URL construction in components.
- Keep code generation in the build workflow; generated outputs are not required to be committed if they can be recreated from source.

## Checks

After route changes, run the frontend type check and build with PHP/Composer dependencies installed. Exercise redirects and form submissions in the relevant Pest feature suite.
