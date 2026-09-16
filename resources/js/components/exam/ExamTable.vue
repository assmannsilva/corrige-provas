<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil, Eye } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ExamStatusBadge from '@/components/exam/ExamStatusBadge.vue';
import type { Exam } from '@/types';

defineProps<{
    exams: Exam[];
}>();

const emit = defineEmits<{
    (e: 'delete', exam: Exam): void;
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-muted/50">
                    <th class="px-4 py-2.5 text-left font-medium">Título</th>
                    <th class="px-4 py-2.5 text-left font-medium">Status</th>
                    <th class="px-4 py-2.5 text-left font-medium">Questões</th>
                    <th class="px-4 py-2.5 text-left font-medium">Pontos</th>
                    <th class="px-4 py-2.5 text-right font-medium">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="exam in exams"
                    :key="exam.id"
                    class="border-b last:border-0 hover:bg-muted/50"
                >
                    <td class="px-4 py-2.5">
                        <Link
                            :href="`/exams/${exam.id}`"
                            class="font-medium hover:underline"
                        >
                            {{ exam.title }}
                        </Link>
                    </td>
                    <td class="px-4 py-2.5">
                        <ExamStatusBadge :status="exam.status" />
                    </td>
                    <td class="px-4 py-2.5 text-muted-foreground">
                        {{ exam.questions_count ?? 0 }}
                    </td>
                    <td class="px-4 py-2.5 text-muted-foreground">
                        {{ exam.total_points }}
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon" class="size-8">
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
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
