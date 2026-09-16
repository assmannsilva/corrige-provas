<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';

defineProps<{
    title: string;
    instructions: string;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    (e: 'update:title', value: string): void;
    (e: 'update:instructions', value: string): void;
}>();
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <Label for="title">Título</Label>
            <Input
                id="title"
                :model-value="title"
                placeholder="Digite o título da prova"
                required
                @update:model-value="emit('update:title', $event as string)"
            />
            <InputError :message="errors?.title" />
        </div>

        <div class="space-y-2">
            <Label for="instructions">Instruções</Label>
            <Textarea
                id="instructions"
                :model-value="instructions"
                placeholder="Digite as instruções da prova para os alunos"
                rows="4"
                @update:model-value="emit('update:instructions', $event as string)"
            />
            <InputError :message="errors?.instructions" />
        </div>
    </div>
</template>
