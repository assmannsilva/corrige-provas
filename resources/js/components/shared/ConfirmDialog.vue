<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    open: boolean;
    title: string;
    description: string;
    confirmLabel?: string;
    variant?: 'default' | 'destructive';
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => !val && emit('cancel')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" :disabled="processing" @click="emit('cancel')">
                    Cancelar
                </Button>
                <Button
                    :variant="variant ?? 'destructive'"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" class="mr-2 size-4" />
                    {{ confirmLabel ?? 'Confirmar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
