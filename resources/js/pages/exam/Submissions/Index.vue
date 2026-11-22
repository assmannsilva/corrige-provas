<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import SubmissionStatusBadge from '@/components/exam/SubmissionStatusBadge.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Exam, Submission } from '@/types';
import exams, { index as examsIndex } from '@/routes/exams';
import submissions from '@/routes/submissions';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: Exam;
}>();

const breadcrumbs = [
    { title: 'Início', href: examsIndex() },
    { title: 'Provas', href: examsIndex() },
    { title: props.exam.title, href: `/exams/${props.exam.id}` },
    { title: 'Envios', href: exams.submissions.index(props.exam.id).url },
];

const submissionsList = computed(() => props.exam.submissions ?? []);

const pendingCount = (submission: Submission) =>
    (submission.answers ?? []).filter((a) => a.needs_review).length;
</script>

<template>
    <Head :title="`Envios - ${exam.title}`" />

    <div class="p-4 md:p-6">
        <PageHeader
            :breadcrumbs="breadcrumbs"
            :title="`Envios - ${exam.title}`"
            :description="`${submissionsList.length} envio(s) para esta prova.`"
        >
            <template #actions>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/exams/${exam.id}`">
                        <ArrowLeft class="mr-2 size-4" />
                        Voltar para a Prova
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="mt-4">
            <EmptyState
                v-if="submissionsList.length === 0"
                title="Nenhum envio ainda"
                description="Envie um PDF de aluno pela página da prova para começar."
            />

            <div v-else class="space-y-3">
                <Card
                    v-for="submission in submissionsList"
                    :key="submission.id"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">
                                    {{
                                        submission.student_name ??
                                        'Aluno não identificado'
                                    }}
                                </CardTitle>
                                <CardDescription>
                                    {{
                                        new Date(
                                            submission.created_at,
                                        ).toLocaleString()
                                    }}
                                </CardDescription>
                            </div>
                            <SubmissionStatusBadge
                                :status="submission.status"
                            />
                        </div>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center gap-2 rounded-lg border bg-muted/40 px-3 py-2"
                            >
                                <span class="text-xs text-muted-foreground">
                                    Nota
                                </span>
                                <span class="text-base font-semibold">
                                    {{ submission.total_score ?? '—' }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    / {{ exam.total_points }}
                                </span>
                            </div>
                            <Badge
                                v-if="pendingCount(submission) > 0"
                                variant="destructive"
                            >
                                <TriangleAlert class="mr-1 size-3" />
                                {{ pendingCount(submission) }} para revisar
                            </Badge>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="submissions.show(submission.id).url">
                                Ver detalhes
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
