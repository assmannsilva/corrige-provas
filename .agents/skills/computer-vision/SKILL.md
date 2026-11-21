---
name: computer-vision
description: Defines computer-vision extraction, alignment and OCR workflow.
---

# Computer Vision

## Scope

Computer vision extracts written and marked answers from uploaded exam pages. Grading is a separate responsibility.

## Pipeline

1. Laravel constructs `BuildComputerVisionPayload` with upload file paths, template PDF path, submission ID and answer-only question regions.
2. The internal Python service validates the restricted request schema (unknown fields forbidden).
3. Python rasterizes files as needed, aligns pages using fiducial markers and crops the answer regions.
4. Local OMR detects selected objective answers. A vision-capable LLM transcribes the relevant answer crops only.
5. Laravel persists answer extraction and queues grading.

## Request schema

Allow `files`, `template`, `questions` (`id`, `regions`), and optional `submission_id`. Each region includes normalized page coordinates and extraction type. Keep Pydantic `extra="forbid"` for unexpected root fields.

## External model

The vision client uses OpenRouter with default `google/gemini-3.7-flash`, configurable via `OPENROUTER_MODEL`. Do not silently fallback to another model. Transmit only the answer crops required for extraction; use synthetic materials in tests and demos.

## Resiliency

Validate file paths against the internal storage root and page/region bounds before processing. Report processing failure and preserve existing submission metadata. Maintain the distinction between unavailable, unreadable and blank answers.

## Tests

Run `python -m pytest python/tests -q` and relevant submission tests. Verify payload schemas, OMR behavior, crop-only transmission and model configuration.
