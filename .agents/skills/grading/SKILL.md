---
name: grading
description: Defines answer evaluation and grading rules.
---

# Automated Grading

## Philosophy

Grading judges previously extracted answers according to a teacher's expected answer and criteria. It is not responsible for reconstructing missing answers. Keep scores bounded by a question's configured weight and present useful feedback for teacher review.

## Objective questions

Single-choice and multiple-choice answers should be graded deterministically using the corresponding grading type. Handle blank, multiple/invalid marks and extraction uncertainty explicitly.

## Essay questions

The grading client sends the question prompt, teacher rubric, expected answer and extracted student answer to OpenRouter. Default `GRADING_LLM_MODEL=google/gemini-3.7-flash`. The model should return a structured score, feedback and review flag. Validate the response schema, reject out-of-range scores and handle parse/network errors.

## Human control

Teachers can override automated grades. Preserve the original extracted text and distinguish teacher overrides from generated feedback. Failed or uncertain extraction should be flagged rather than assigned an unsupported confident score.

## Tests

Cover rubric fidelity, objective rules, blank/failed extraction, score clamping, manual override, model override and HTTP request payload structure.
