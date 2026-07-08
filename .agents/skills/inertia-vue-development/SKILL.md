---
name: inertia-vue-development
description: Vue 3, Inertia.js, TypeScript and application interface conventions.
---

# Inertia Vue Development

## Structure

- Use `resources/js/pages` for route-level pages and `resources/js/components` for reusable interface elements.
- Keep types for shared server payloads under `resources/js/types` or adjacent to their owning components.
- Reuse layouts, dialogs, form fields and feedback components already present in the project.
- Follow the established Vue 3 Composition API and `<script setup lang="ts">` conventions.

## Requests and navigation

Use Inertia's `useForm` for mutations, preserving validation errors and processing state. Prefer Wayfinder-generated route functions to manually assembled URLs. Authorize every mutation in Laravel even when UI buttons are hidden or disabled.

For uploads, represent files as `File[]` in the form, expose progress/errors clearly, and keep dialog close/reset behavior consistent with pending requests. The submission flow asks for a student's name and allows the class field to be blank.

## Interaction quality

Provide accessible labels and focus states. Handle loading, empty and error states. Confirm destructive actions and make mobile capture controls usable on narrow screens.

## Verification

Run `npm run lint:check`, `npm run format:check`, `npm run types:check` and `npm run build` after frontend changes. Build generation may require PHP and Composer dependencies because Wayfinder derives typed routes from Laravel.
