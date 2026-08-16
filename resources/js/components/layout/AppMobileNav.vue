<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { NavItem } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Corrige Provas';

defineProps<{
    items: NavItem[];
}>();
</script>

<template>
    <Sheet>
        <SheetTrigger as-child>
            <slot />
        </SheetTrigger>
        <SheetContent side="left" class="w-64 p-6">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <AppLogoIcon class="size-5" />
                    <span>{{ appName }}</span>
                </SheetTitle>
            </SheetHeader>
            <nav class="mt-6 flex flex-col gap-1">
                <Button
                    v-for="item in items"
                    :key="item.title"
                    variant="ghost"
                    class="justify-start"
                    as-child
                >
                    <Link :href="item.href">
                        <component :is="item.icon" class="mr-2 size-4" />
                        {{ item.title }}
                    </Link>
                </Button>
            </nav>
        </SheetContent>
    </Sheet>
</template>
