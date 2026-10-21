<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    status: string;
}>();

const config: Record<
    string,
    {
        label: string;
        variant: 'default' | 'secondary' | 'outline' | 'destructive';
    }
> = {
    uploaded: { label: 'Enviado', variant: 'secondary' },
    processing: { label: 'Processando', variant: 'outline' },
    computer_vision_completed: {
        label: 'Visão Computacional Concluída',
        variant: 'default',
    },
    grading: { label: 'Corrigindo', variant: 'outline' },
    completed: { label: 'Concluído', variant: 'default' },
    failed: { label: 'Falhou', variant: 'destructive' },
};

const current = (status: string) =>
    config[status] ?? { label: status, variant: 'secondary' as const };
</script>

<template>
    <Badge :variant="current(status).variant">
        {{ current(status).label }}
    </Badge>
</template>
