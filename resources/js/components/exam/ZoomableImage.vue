<script setup lang="ts">
import { Minus, Plus } from '@lucide/vue';
import Panzoom from '@panzoom/panzoom';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    src: string;
    alt?: string;
}>();

const image = ref<HTMLImageElement | null>(null);

let instance: ReturnType<typeof Panzoom> | null = null;

onMounted(() => {
    if (!image.value) {
        return;
    }

    instance = Panzoom(image.value, {
        maxScale: 8,
        minScale: 1,
        startScale: 1,
        contain: 'outside',
        cursor: 'grab',
        increment: 0.25,
    });
});

onBeforeUnmount(() => {
    instance?.destroy();
    instance = null;
});

const zoomIn = (): void => {
    instance?.zoomIn();
};

const zoomOut = (): void => {
    instance?.zoomOut();
};
</script>

<template>
    <div class="relative overflow-hidden rounded-lg border bg-muted">
        <div class="w-full touch-none overflow-hidden">
            <img
                ref="image"
                :src="props.src"
                :alt="props.alt"
                class="block max-h-[70vh] min-h-[40vh] w-full object-contain select-none"
            />
        </div>

        <div class="absolute right-2 bottom-2 flex gap-1">
            <Button
                type="button"
                variant="secondary"
                size="icon"
                aria-label="Diminuir zoom"
                @click="zoomOut"
            >
                <Minus />
            </Button>
            <Button
                type="button"
                variant="secondary"
                size="icon"
                aria-label="Aumentar zoom"
                @click="zoomIn"
            >
                <Plus />
            </Button>
        </div>
    </div>
</template>
