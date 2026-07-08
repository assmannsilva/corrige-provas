---
name: submission
description: Defines submission uploads, student identification and processing state.
---

# Submission Domain

## Concepts

A `Submission` belongs to an exam and stores the student name (required) and class (optional). It has ordered `SubmissionFile` records, extracted `SubmissionAnswer` records and `ComputerVisionProcessing` logs.

## Upload workflow

The teacher selects files or camera captures, enters the name and optionally the class, then confirms the upload. The backend authorizes the exam, validates each file and stores the originals in private local storage. The service persists the submission and its files, then dispatches the computer-vision job.

Keep file order stable. A submission can contain one PDF or multiple image pages. Remove orphan uploaded files when the associated transaction fails.

## Processing

The jobs and enums under `app/Enums` and `app/Jobs` define the accepted state transitions. Computer vision extracts the answers; grading evaluates them. Processing may be retried, and error details should be associated with the processing record.

A reprocessing request refreshes answer extraction and grading without changing the submission's student name and class. The teacher may update those fields using the authorized submission endpoint.

## Authorization and storage

Only the owning teacher may view, update, reprocess or download related answer crops. Store originals and generated crops privately; return them through authorized controller actions.

## Verification

Test validation, ownership, status transitions, file ordering, failed uploads, duplicate processing, score overrides and successful reprocessing.
