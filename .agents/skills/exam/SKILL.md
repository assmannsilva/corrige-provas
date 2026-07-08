---
name: exam
description: Defines exams, question types, PDF rendering and publication workflow.
---

# Exam Domain

## Core entities

`Exam` belongs to a teacher and has ordered `Question` records and submitted `Submission` records. The exam has a title, optional instructions, status, generated PDF path and generation timestamp. Its computed total is the sum of question weights.

`Question` stores its type, statement, weight, order, options and expected answers as appropriate, and `computer_vision_region` metadata describing the answer area.

## Lifecycle

- `draft`: the teacher may edit, delete and reorder questions.
- `published`: the question definitions are immutable and the generated exam may receive submissions.
- `archived`: historical data remains available for authorized review.

Policies and services enforce these rules on the backend; frontend checks provide feedback but are not authorization.

## Question types

The `QuestionTypeRegistry` selects the domain behavior for essays, single-choice and multiple-choice questions. The `RendererRegistry` chooses the corresponding PDF renderer. Keep the question builder, validation, expected-answer representation and printable version in agreement.

## PDF generation

The generated PDF includes a printable header and answer areas. The renderer measures the answer regions (`page`, `x`, `y`, `width`, `height` and an optional `key`) and stores those coordinates in the corresponding questions. Fiducial markers support page alignment after scanning.

When changing a renderer, verify both the printed layout and the coordinates returned by `PdfRenderer::measure()` for single-page and multi-page exams.

## Verification

Test ownership, state transitions, question order, weight validation, rendering by question type, PDF generation and download permissions.
