---
name: exam
description: Defines exams, question types, PDF rendering and publication workflow.
---

# Exam Domain

## Core entities

`Exam` belongs to a teacher and has ordered `Question` records and submitted `Submission` records. The exam has a title, optional instructions, status, generated PDF path and generation timestamp. Its computed total is the sum of question weights.

`Question` has its type, statement, weight, order, options/answers as appropriate, and `computer_vision_region` metadata for the **answer area only**.

## Workflow

- `draft`: teacher may edit, delete and reorder questions.
- `published`: question definitions become immutable; PDF can be generated and submissions can be uploaded.
- `archived`: historical data remains accessible under authorization; no new work should be accepted unless explicitly supported.

The controller must call an authorized service; do not let frontend-only checks enforce status.

## Question types and renderers

The `QuestionTypeRegistry` selects the domain type and the `RendererRegistry` selects the PDF renderer. Objective questions and essays have distinct answer representations and evaluation rules. Keep the UI builder and PDF rendering consistent with the registry.

## PDF and answer regions

The generated printable exam may contain a blank visual name/class header for the student, but the application never measures, persists or transmits its coordinates. PDF measurement returns only answer regions (`page`, `x`, `y`, `width`, `height`, optional `key`). Question regions are normalized to page coordinates and stored in `Question.computer_vision_region`. Fiducial alignment markers help match uploaded pages to the template.

## Tests

Check exam ownership, valid transitions, editor behavior, question ordering, PDF generation, PDF downloads and that PDF measurement contains answer regions only.
