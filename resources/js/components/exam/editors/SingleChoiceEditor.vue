<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Option = { text: string; correct: boolean };

const props = defineProps<{
    modelValue: Record<string, unknown>;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, unknown>): void;
}>();

const options = (): Option[] => {
    const raw =
        (props.modelValue.options as Partial<Option>[] | undefined) ?? [];

    return raw.map((o) => ({
        text: (o.text as string) ?? '',
        correct: Boolean(o.correct),
    }));
};

const commit = (next: Option[]) => {
    emit('update:modelValue', { ...props.modelValue, options: next });
};

const setText = (index: number, text: string) => {
    commit(options().map((o, i) => (i === index ? { ...o, text } : o)));
};

const setCorrect = (index: number) => {
    commit(options().map((o, i) => ({ ...o, correct: i === index })));
};

const addOption = () => {
    commit([...options(), { text: '', correct: false }]);
};

const removeOption = (index: number) => {
    commit(options().filter((_, i) => i !== index));
};
</script>

<template>
    <div class="space-y-3">
        <p class="text-xs text-muted-foreground">
            Marque a opção correta. Exatamente uma deve estar correta.
        </p>

        <div class="space-y-2">
            <div
                v-for="(option, idx) in options()"
                :key="idx"
                class="flex items-center gap-2"
            >
                <button
                    type="button"
                    class="shrink-0"
                    :aria-pressed="option.correct"
                    :title="option.correct ? 'Correta' : 'Marcar como correta'"
                    @click="setCorrect(idx)"
                >
                    <span
                        class="flex size-5 items-center justify-center rounded-full border-2 transition-colors"
                        :class="
                            option.correct
                                ? 'border-primary'
                                : 'border-gray-300'
                        "
                    >
                        <span
                            v-if="option.correct"
                            class="size-2.5 rounded-full bg-primary"
                        />
                    </span>
                </button>
                <span
                    class="w-5 shrink-0 text-center text-xs font-medium text-muted-foreground"
                >
                    {{ String.fromCharCode(65 + idx) }}
                </span>
                <Input
                    type="text"
                    :model-value="option.text"
                    :placeholder="`Opção ${String.fromCharCode(65 + idx)}`"
                    class="flex-1"
                    @update:model-value="setText(idx, $event as string)"
                />
                <Button
                    v-if="options().length > 2"
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8 shrink-0 text-muted-foreground"
                    @click="removeOption(idx)"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <Button
            v-if="options().length < 10"
            type="button"
            variant="ghost"
            size="sm"
            class="text-muted-foreground"
            @click="addOption"
        >
            <Plus class="mr-1 size-3" />
            Adicionar opção
        </Button>
    </div>
</template>
