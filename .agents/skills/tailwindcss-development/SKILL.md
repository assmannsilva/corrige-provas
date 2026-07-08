---
name: tailwindcss-development
description: Tailwind CSS and accessible component conventions for the Vue interface.
---

# Tailwind CSS Development

## Design system

Reuse the existing components in `resources/js/components/ui` for controls, labels, dialogs and feedback. Prefer consistent spacing and typography to per-page custom CSS. Tailwind utility classes should remain readable and match the project's formatting rules.

## Responsive and accessible layouts

- Implement and test narrow-screen layouts first, then verify desktop behavior.
- Associate each input with a label and show backend validation messages beside the relevant control.
- Preserve visible keyboard focus, semantic buttons and appropriate dialog focus management.
- Use disabled and loading states during uploads, PDF generation and mutations.
- Keep image-preview, camera and file-upload controls operable on touch devices.

Use existing page and dialog patterns rather than adding a new primitive for a one-off layout. Validate changes with `npm run format:check` and `npm run build`.
