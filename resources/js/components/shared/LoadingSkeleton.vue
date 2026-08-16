<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

defineProps<{
    variant?: 'table' | 'card' | 'form' | 'text';
    rows?: number;
}>();
</script>

<template>
    <div v-if="variant === 'table' || !variant" class="space-y-2">
        <div v-for="i in (rows ?? 5)" :key="i" class="flex items-center gap-4">
            <Skeleton class="h-5 w-full" />
        </div>
    </div>

    <div v-else-if="variant === 'card'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div v-for="i in (rows ?? 3)" :key="i" class="rounded-lg border p-4 space-y-3">
            <Skeleton class="h-5 w-3/4" />
            <Skeleton class="h-4 w-1/2" />
            <Skeleton class="h-8 w-24" />
        </div>
    </div>

    <div v-else-if="variant === 'form'" class="space-y-4">
        <div class="space-y-2">
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-10 w-full" />
        </div>
        <div class="space-y-2">
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-24 w-full" />
        </div>
    </div>

    <div v-else-if="variant === 'text'" class="space-y-2">
        <Skeleton class="h-4 w-full" />
        <Skeleton class="h-4 w-5/6" />
        <Skeleton class="h-4 w-4/6" />
    </div>
</template>
