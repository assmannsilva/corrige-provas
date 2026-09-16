import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { Question } from '@/types';

type ChoiceOption = { text?: string; correct?: boolean };

export function expectedAnswersFor(
    question: Question | undefined | null,
): string[] {
    if (!question) {
        return [];
    }

    const metadata = (question.metadata ?? {}) as Record<string, unknown>;

    switch (question.type) {
        case 'essay': {
            const value = metadata.expected_answer;

            return typeof value === 'string' && value.trim() !== ''
                ? [value]
                : [];
        }
        case 'single_choice':
        case 'multiple_choice': {
            const options =
                (metadata.options as ChoiceOption[] | undefined) ?? [];

            return options
                .map((option, index) => ({
                    ...option,
                    letter: String.fromCharCode(65 + index),
                }))
                .filter((option) => option.correct)
                .map((option) => `${option.letter}) ${option.text ?? ''}`);
        }
        default:
            return [];
    }
}

export function useExpectedAnswers(
    question: MaybeRefOrGetter<Question | undefined | null>,
) {
    return computed(() => expectedAnswersFor(toValue(question)));
}
