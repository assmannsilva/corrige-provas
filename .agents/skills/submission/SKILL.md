---
name: submission
description: Defines submission uploads, local student identification and processing state.
---

# Submission Domain

## Concepts

A `Submission` belongs to an exam and stores a teacher-entered `student_name` (required) and `student_class` (optional). A submission has ordered `SubmissionFile` records, extracted `SubmissionAnswer` records and `ComputerVisionProcessing` logs.

## Upload

The teacher selects files or camera captures, opens the identification modal, enters the required name and optionally the class, then confirms. The server authorizes the exam, validates the fields and files, stores files under private local storage, persists the identity and dispatches the computer-vision job. On database failure, remove orphan uploads.

## Reprocessing

Reprocessing may update extraction, answer crops, grading and processing status. Preserve existing submission details during reprocessing. The teacher can edit the name and class through the authorized submission endpoint.

## Status and orchestration

`uploaded -> processing -> extracted/grading -> graded`, with failed states recorded for recovery. The concrete enums and jobs in `app/Enums` and `app/Jobs` are the source of truth for transitions. Keep extraction and grading jobs independent and idempotent where possible.

## Ownership and storage

Only the owning teacher may upload, view, edit or reprocess. Store original scans and answer crops privately; expose them only through authorized endpoints.

## Tests

Cover missing/whitespace names, optional class, permissions, invalid files, multi-file ordering, failure cleanup, reprocessing metadata preservation and network payload validation.
