<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    modelValue: Record<string, unknown>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, unknown>): void;
}>();

const update = (key: string, value: unknown) => {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
};
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <Label for="essay-lines">Linhas</Label>
            <Input
                id="essay-lines"
                type="number"
                :min="1"
                :max="50"
                :model-value="(modelValue.lines as number) ?? 10"
                @update:model-value="update('lines', Number($event))"
            />
        </div>

        <div class="space-y-2">
            <Label for="essay-answer">Resposta Esperada</Label>
            <Textarea
                id="essay-answer"
                :model-value="(modelValue.expected_answer as string) ?? ''"
                placeholder="Resposta esperada para correção"
                rows="3"
                @update:model-value="
                    update('expected_answer', $event as string)
                "
            />
        </div>
    </div>
</template>
