<script setup lang="ts">
import EssayEditor from '@/components/exam/editors/EssayEditor.vue';
import MultipleChoiceEditor from '@/components/exam/editors/MultipleChoiceEditor.vue';
import SingleChoiceEditor from '@/components/exam/editors/SingleChoiceEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { QuestionTypeDefinition } from '@/types';

defineProps<{
    questionTypes: QuestionTypeDefinition[];
    type: string;
    statement: string;
    weight: number;
    gradingNotes: string;
    metadata: Record<string, unknown>;
    errors?: Record<string, string>;
    processing?: boolean;
    submitLabel?: string;
    lockType?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:type', value: string): void;
    (e: 'update:statement', value: string): void;
    (e: 'update:weight', value: number): void;
    (e: 'update:gradingNotes', value: string): void;
    (e: 'update:metadata', value: Record<string, unknown>): void;
    (e: 'submit'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <div class="rounded-lg border bg-card p-4">
        <form @submit.prevent="emit('submit')" class="space-y-4">
            <div class="space-y-2">
                <Label for="q-type">Tipo</Label>
                <Select
                    :model-value="type"
                    :disabled="lockType"
                    @update:model-value="emit('update:type', $event as string)"
                >
                    <SelectTrigger id="q-type">
                        <SelectValue placeholder="Selecionar tipo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="qt in questionTypes"
                            :key="qt.identifier"
                            :value="qt.identifier"
                        >
                            {{ qt.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors?.type" />
            </div>

            <div class="space-y-2">
                <Label for="q-statement">Enunciado</Label>
                <Textarea
                    id="q-statement"
                    :model-value="statement"
                    placeholder="Digite o enunciado da questão"
                    rows="3"
                    @update:model-value="
                        emit('update:statement', $event as string)
                    "
                />
                <InputError :message="errors?.statement" />
            </div>

            <div class="space-y-2">
                <Label for="q-weight">Peso (pontos)</Label>
                <Input
                    id="q-weight"
                    type="number"
                    :model-value="weight"
                    min="0.1"
                    step="0.1"
                    @update:model-value="emit('update:weight', Number($event))"
                />
                <InputError :message="errors?.weight" />
            </div>

            <div class="space-y-4 rounded-md border bg-muted/30 p-3">
                <p class="text-sm font-medium">Configuração</p>

                <EssayEditor
                    v-if="type === 'essay'"
                    :model-value="metadata"
                    @update:model-value="emit('update:metadata', $event)"
                />
                <SingleChoiceEditor
                    v-else-if="type === 'single_choice'"
                    :model-value="metadata"
                    @update:model-value="emit('update:metadata', $event)"
                />
                <MultipleChoiceEditor
                    v-else-if="type === 'multiple_choice'"
                    :model-value="metadata"
                    @update:model-value="emit('update:metadata', $event)"
                />
            </div>

            <div v-if="type === 'essay'" class="space-y-2">
                <Label for="q-notes">Notas de Correção</Label>
                <Textarea
                    id="q-notes"
                    :model-value="gradingNotes"
                    placeholder="Notas para corrigir esta questão"
                    rows="2"
                    @update:model-value="
                        emit('update:gradingNotes', $event as string)
                    "
                />
                <InputError :message="errors?.grading_notes" />
            </div>

            <div class="flex gap-2">
                <Button type="button" variant="outline" @click="emit('cancel')">
                    Cancelar
                </Button>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" class="mr-2 size-4" />
                    {{ submitLabel ?? 'Adicionar Questão' }}
                </Button>
            </div>
        </form>
    </div>
</template>
