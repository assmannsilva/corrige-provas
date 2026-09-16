<script setup lang="ts">
import { MoreHorizontal, ArrowUp, ArrowDown, Pencil, Trash } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Question } from '@/types';

defineProps<{
    question: Question;
    typeLabel: string;
    isFirst: boolean;
    isLast: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit', question: Question): void;
    (e: 'moveUp', question: Question): void;
    (e: 'moveDown', question: Question): void;
    (e: 'delete', question: Question): void;
}>();
</script>

<template>
    <div class="flex items-start justify-between rounded-lg border p-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">#{{ question.order + 1 }}</span>
                <span class="text-xs font-medium text-muted-foreground">{{ typeLabel }}</span>
            </div>
            <p class="mt-1 whitespace-pre-wrap text-sm font-medium">
                {{ question.statement.replace(/\[\[.*?\]\]/gs, '_____') || 'Questão sem título' }}
            </p>
            <p class="mt-0.5 text-xs text-muted-foreground">{{ question.weight }} pts</p>
        </div>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="size-8 shrink-0">
                    <MoreHorizontal class="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="emit('edit', question)">
                    <Pencil class="mr-2 size-4" />
                    Editar
                </DropdownMenuItem>
                <DropdownMenuItem
                    :disabled="isFirst"
                    @click="emit('moveUp', question)"
                >
                    <ArrowUp class="mr-2 size-4" />
                    Mover para cima
                </DropdownMenuItem>
                <DropdownMenuItem
                    :disabled="isLast"
                    @click="emit('moveDown', question)"
                >
                    <ArrowDown class="mr-2 size-4" />
                    Mover para baixo
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    class="text-destructive"
                    @click="emit('delete', question)"
                >
                    <Trash class="mr-2 size-4" />
                    Excluir
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
