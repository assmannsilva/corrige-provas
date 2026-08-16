<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <AppHeader />
        <main class="flex-1">
            <slot />
        </main>
        <Toaster />
    </div>
</template>
