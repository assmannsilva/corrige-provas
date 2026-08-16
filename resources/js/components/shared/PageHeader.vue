<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import Heading from '@/components/Heading.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs: BreadcrumbItem[];
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="space-y-2">
        <Breadcrumbs :breadcrumbs="breadcrumbs" />
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading :title="title" :description="description" />
            <div
                v-if="$slots.actions"
                class="flex flex-wrap items-center gap-2"
            >
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
