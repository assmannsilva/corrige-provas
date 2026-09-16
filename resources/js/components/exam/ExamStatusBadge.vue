<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    status: string;
}>();

const variant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    switch (status) {
        case 'draft':
            return 'secondary';
        case 'published':
            return 'default';
        case 'archived':
            return 'outline';
        default:
            return 'secondary';
    }
};

const label = (status: string): string => {
    switch (status) {
        case 'draft':
            return 'Rascunho';
        case 'published':
            return 'Publicada';
        case 'archived':
            return 'Arquivada';
        default:
            return status;
    }
};
</script>

<template>
    <Badge :variant="variant(status)">
        {{ label(status) }}
    </Badge>
</template>
