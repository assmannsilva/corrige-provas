---
name: inertia-vue-development
description: Guides the Inertia Vue application, forms and navigation.
---

# Inertia Vue Development

The frontend uses Vue 3, TypeScript, Inertia and Tailwind. Keep page components in `resources/js/pages`, reusable components in `resources/js/components`, and shared interfaces in `resources/js/types`. Build forms with Inertia `useForm`, display server validation errors and use the generated Wayfinder routes instead of manually concatenating endpoints. Keep form processing states and modal close behavior safe during uploads. Files and camera captures belong to the submission upload component; name is required and class optional in its modal. Ensure displayed student data comes from the authorized local submission response and is never sent to an external AI service.
