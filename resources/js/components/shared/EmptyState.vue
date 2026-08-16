<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    icon?: any;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <component
            :is="icon ?? FileText"
            class="size-12 text-muted-foreground/60"
        />
        <h3 class="mt-4 text-lg font-semibold">{{ title }}</h3>
        <p class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
        <Button v-if="actionLabel && actionHref" class="mt-4" as-child>
            <Link :href="actionHref">{{ actionLabel }}</Link>
        </Button>
    </div>
</template>
