<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useExpectedAnswers } from '@/composables/useExpectedAnswers';
import type { Question } from '@/types';

const props = defineProps<{
    question: Question;
    typeLabel: string;
}>();

const expectedAnswers = useExpectedAnswers(() => props.question);
</script>

<template>
    <div class="rounded-lg border p-4">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold"
                        >#{{ question.order + 1 }}</span
                    >
                    <span class="text-xs text-muted-foreground">{{
                        typeLabel
                    }}</span>
                </div>
                <p class="mt-1 text-sm whitespace-pre-wrap">
                    {{ question.statement }}
                </p>
            </div>
            <Badge variant="secondary" class="ml-2 shrink-0"
                >{{ question.weight }} pts</Badge
            >
        </div>

        <div
            v-if="question.rendered_html"
            class="mt-4"
            v-html="question.rendered_html"
        />

        <div class="mt-4 space-y-2">
            <Collapsible v-if="expectedAnswers.length">
                <CollapsibleTrigger as-child>
                    <Button variant="ghost" size="sm" class="group -ml-2">
                        <ChevronDown
                            class="mr-1 size-4 transition-transform duration-200 group-data-[state=open]:rotate-180"
                        />
                        Resposta Esperada
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div
                        class="mt-2 ml-6 space-y-1 rounded-md bg-muted p-3 text-sm text-muted-foreground"
                    >
                        <p
                            v-for="(answer, index) in expectedAnswers"
                            :key="index"
                        >
                            {{ answer }}
                        </p>
                    </div>
                </CollapsibleContent>
            </Collapsible>

            <Collapsible
                v-if="question.type === 'essay' && question.grading_notes"
            >
                <CollapsibleTrigger as-child>
                    <Button variant="ghost" size="sm" class="group -ml-2">
                        <ChevronDown
                            class="mr-1 size-4 transition-transform duration-200 group-data-[state=open]:rotate-180"
                        />
                        Notas de Correção
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div
                        class="mt-2 ml-6 rounded-md bg-muted p-3 text-sm text-muted-foreground"
                    >
                        {{ question.grading_notes }}
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </div>
</template>
