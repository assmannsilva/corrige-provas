---
name: grading
description: Defines objective and discursive answer evaluation and teacher review.
---

# Automated Grading

## Responsibilities

Grading uses extracted answers and the teacher's expected response, rubric and question weight. It must not invent an answer when extraction failed, and it must not exceed the question's configured score range.

## Objective questions

Grade single-choice and multiple-choice answers with deterministic rules. Explicitly handle blank submissions, inconsistent markings and unrecognized options. Do not call an external language model for objective grading.

## Essay questions

The grading client sends the question text, expected response, criteria, weight and extracted answer to OpenRouter. `GRADING_LLM_MODEL` selects the model, with `google/gemini-3.7-flash` as the configured default. Request a structured score, feedback and any review indicator, and validate the returned types and score bounds before persisting.

## Teacher review

Expose feedback and any uncertainty to the teacher. A teacher can override generated scores, and the system must update totals consistently. Keep the extracted answer distinct from the generated feedback or human adjustments.

## Error cases

Treat missing text, failed extraction, timeouts, malformed JSON and out-of-range scores explicitly. Record failures for investigation and retry without reporting unsupported confident results.

## Verification

Cover objective grading rules, rubric-based essays, empty answers, schema failures, score limits, model configuration, manual override and queued execution using HTTP fakes.
