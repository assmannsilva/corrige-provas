<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil, Eye } from '@lucide/vue';
import ExamStatusBadge from '@/components/exam/ExamStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Exam } from '@/types';

defineProps<{
    exams: Exam[];
}>();

const emit = defineEmits<{
    (e: 'delete', exam: Exam): void;
}>();
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="exam in exams"
            :key="exam.id"
            class="flex items-center justify-between rounded-lg border p-3"
        >
            <div class="min-w-0 flex-1">
                <Link
                    :href="`/exams/${exam.id}`"
                    class="block truncate font-medium hover:underline"
                >
                    {{ exam.title }}
                </Link>
                <div
                    class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <ExamStatusBadge :status="exam.status" />
                    <span>{{ exam.questions_count ?? 0 }} questões</span>
                    <span>{{ exam.total_points }} pts</span>
                </div>
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="size-8 shrink-0">
                        <MoreHorizontal class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem as-child>
                        <Link :href="`/exams/${exam.id}`">
                            <Eye class="mr-2 size-4" />
                            Ver
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="exam.status === 'draft'" as-child>
                        <Link :href="`/exams/${exam.id}/edit`">
                            <Pencil class="mr-2 size-4" />
                            Editar
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="exam.status === 'draft'"
                        class="text-destructive"
                        @click="emit('delete', exam)"
                    >
                        Excluir
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
