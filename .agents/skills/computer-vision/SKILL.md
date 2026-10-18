---
name: computer-vision
description: Defines image alignment, answer extraction and vision service integration.
---

# Computer Vision

## Purpose

The Python service reads answer regions from a student's scanned or photographed exam. It is responsible for extraction, not grading.

## Pipeline

1. Laravel prepares the processing request with files, template PDF and question answer regions.
2. FastAPI validates the request and passes it to the extraction pipeline.
3. Images and PDF pages are rasterized and aligned against the original template using fiducial markers.
4. Objective mark regions are classified locally using image processing.
5. Handwritten answer regions are cropped and transcribed through the configured vision model.
6. Laravel persists extracted answers and triggers grading.

## Data contract

`ProcessRequest` accepts `files`, `template`, `questions` and optional `submission_id`. Each question contains an `id` and a list of normalized regions. A region carries the page index, coordinates, optional key and extraction type. Preserve page order and stable question identifiers across services.

## Recognition

The OpenRouter model is configured through `OPENROUTER_MODEL` and defaults to `google/gemini-3.7-flash`. Transcription uses multiple samples and reports uncertainty when interpretations diverge. Objective bubbles are evaluated by local ink-density analysis.

## Failures and observability

Report unreadable or missing pages, out-of-range coordinates and external service failures as processing errors. Keep saved crops in the private storage hierarchy, and ensure file paths remain within the configured storage root. Distinguish blank answers from failed extraction.

## Tests

Run `python -m pytest python/tests -q` and the submission feature tests. Include tests for alignment, OMR, transcription response parsing, uncertain recognition, invalid input and retry behavior.
