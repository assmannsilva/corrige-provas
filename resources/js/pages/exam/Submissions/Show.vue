<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Image as ImageIcon,
    Pencil,
    RefreshCw,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SubmissionStatusBadge from '@/components/exam/SubmissionStatusBadge.vue';
import ZoomableImage from '@/components/exam/ZoomableImage.vue';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { expectedAnswersFor } from '@/composables/useExpectedAnswers';
import exams, { index as examsIndex } from '@/routes/exams';
import submissions from '@/routes/submissions';
import type {
    Submission,
    SubmissionAnswer,
    SubmissionAnswerStatus,
} from '@/types';

defineOptions({
    layout: null,
});

const props = defineProps<{
    submission: Submission;
}>();

const exam = computed(() => props.submission.exam);

const breadcrumbs = [
    { title: 'Início', href: examsIndex() },
    { title: 'Provas', href: examsIndex() },
    {
        title: exam.value?.title ?? 'Prova',
        href: `/exams/${props.submission.exam_id}`,
    },
    {
        title: 'Envios',
        href: exams.submissions.index(props.submission.exam_id).url,
    },
    {
        title: props.submission.student_name ?? `#${props.submission.id}`,
        href: submissions.show(props.submission.id).url,
    },
];

const answerVariant = (status: SubmissionAnswerStatus) => {
    switch (status) {
        case 'extracted':
            return 'default';
        case 'failed':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const expectedAnswers = (answer: SubmissionAnswer) =>
    expectedAnswersFor(answer.question);

const reprocess = () => {
    router.post(
        submissions.reprocess(props.submission.id).url,
        {},
        { preserveScroll: true },
    );
};

const sortedAnswers = computed(() => {
    const order = new Map(
        (exam.value?.questions ?? []).map((q, i) => [q.id, i]),
    );

    return [...(props.submission.answers ?? [])].sort(
        (a, b) =>
            (order.get(a.question_id) ?? 0) - (order.get(b.question_id) ?? 0),
    );
});

const pendingReviewCount = computed(
    () => (props.submission.answers ?? []).filter((a) => a.needs_review).length,
);

const maxScore = computed(() => exam.value?.total_points ?? 0);
const scorePercent = computed(() => {
    if (maxScore.value <= 0) {
        return null;
    }

    return Math.round(
        ((props.submission.total_score ?? 0) / maxScore.value) * 100,
    );
});

const editingStudent = ref(false);
const nameDraft = ref('');
const classDraft = ref('');

const startEditStudent = () => {
    nameDraft.value = props.submission.student_name ?? '';
    classDraft.value = props.submission.student_class ?? '';
    editingStudent.value = true;
};

const saveStudent = () => {
    router.patch(
        submissions.student.update(props.submission.id).url,
        { student_name: nameDraft.value, student_class: classDraft.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingStudent.value = false;
            },
        },
    );
};

const cropAnswer = ref<SubmissionAnswer | null>(null);

const editing = ref<number | null>(null);
const answerForm = useForm<{ score: string; feedback: string }>({
    score: '',
    feedback: '',
});

const startEdit = (answer: SubmissionAnswer) => {
    editing.value = answer.id;
    answerForm.score = answer.score === null ? '' : String(answer.score);
    answerForm.feedback = answer.feedback ?? '';
    answerForm.clearErrors();
};

const cancelEdit = () => {
    editing.value = null;
};

const saveEdit = (answer: SubmissionAnswer) => {
    answerForm.patch(
        submissions.answers.update({
            submission: props.submission.id,
            answer: answer.id,
        }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Envio - ${submission.student_name ?? submission.id}`" />

    <div class="p-4 md:p-6">
        <PageHeader
            :breadcrumbs="breadcrumbs"
            :title="submission.student_name ?? 'Aluno não identificado'"
            :description="`Envio para '${exam?.title ?? ''}'`"
        >
            <template #actions>
                <SubmissionStatusBadge :status="submission.status" />
                <Button variant="outline" size="sm" @click="reprocess">
                    <RefreshCw class="mr-2 size-4" />
                    Reprocessar
                </Button>
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="exams.submissions.index(submission.exam_id).url"
                    >
                        <ArrowLeft class="mr-2 size-4" />
                        Voltar
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 p-4"
        >
            <div class="flex items-center gap-3">
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Aluno
                    </p>
                    <div class="flex items-center gap-2">
                        <p
                            v-if="!editingStudent"
                            class="text-base font-semibold"
                        >
                            {{ submission.student_name ?? 'Não identificado' }}
                            <span
                                v-if="submission.student_class"
                                class="font-normal text-muted-foreground"
                            >
                                · {{ submission.student_class }}
                            </span>
                        </p>
                        <template v-else>
                            <Input
                                v-model="nameDraft"
                                class="max-w-xs"
                                placeholder="Nome"
                                autofocus
                            />
                            <Input
                                v-model="classDraft"
                                class="max-w-[10rem]"
                                placeholder="Turma"
                            />
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    v-if="!editingStudent"
                    variant="outline"
                    size="sm"
                    @click="startEditStudent"
                >
                    <Pencil class="mr-2 size-4" />
                    Editar aluno
                </Button>
                <template v-else>
                    <Button size="sm" @click="saveStudent">
                        <CheckCircle2 class="mr-2 size-4" />
                        Salvar
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="editingStudent = false"
                    >
                        Cancelar
                    </Button>
                </template>
            </div>
        </div>

        <div
            class="mt-3 flex items-center justify-between rounded-lg border bg-primary/5 p-4"
        >
            <div>
                <p class="text-xs font-medium text-muted-foreground">
                    Nota final
                </p>
                <p class="text-2xl font-bold">
                    {{ submission.total_score ?? '—' }}
                    <span class="text-base font-medium text-muted-foreground">
                        / {{ maxScore }}
                    </span>
                </p>
            </div>
            <p
                v-if="scorePercent !== null"
                class="text-sm text-muted-foreground"
            >
                {{ scorePercent }}% de aproveitamento
            </p>
        </div>

        <div
            v-if="pendingReviewCount > 0"
            class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
        >
            <TriangleAlert class="mr-2 inline size-4" />
            {{ pendingReviewCount }} questão(ões) precisam de revisão manual.
        </div>

        <div class="mt-4 space-y-4">
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Respostas</CardTitle>
                    <CardDescription>
                        {{ submission.answers?.length ?? 0 }} resposta(s)
                        extraída(s).
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="!sortedAnswers.length"
                        title="Nenhuma resposta ainda"
                        description="Os resultados aparecerão aqui quando o processamento terminar."
                    />

                    <div v-else class="space-y-3">
                        <div
                            v-for="answer in sortedAnswers"
                            :key="answer.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium">
                                    {{
                                        answer.question?.statement ??
                                        `Questão #${answer.question_id}`
                                    }}
                                </p>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Badge
                                        v-if="answer.needs_review"
                                        variant="destructive"
                                        >Revisar</Badge
                                    >
                                    <Badge
                                        :variant="answerVariant(answer.status)"
                                    >
                                        {{ answer.status }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="mt-2 space-y-1 text-sm">
                                <p class="text-muted-foreground">
                                    <span class="font-medium">Extraído:</span>
                                    {{ answer.raw_text || '—' }}
                                </p>
                                <div
                                    v-if="expectedAnswers(answer).length"
                                    class="rounded-md bg-muted p-2"
                                >
                                    <p class="font-medium">Resposta esperada</p>
                                    <p
                                        v-for="(
                                            expected, index
                                        ) in expectedAnswers(answer)"
                                        :key="index"
                                        class="text-muted-foreground"
                                    >
                                        {{ expected }}
                                    </p>
                                </div>
                                <div v-if="answer.crop_path" class="mt-3">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="cropAnswer = answer"
                                    >
                                        <ImageIcon class="mr-2 size-4" />
                                        Ver recorte
                                    </Button>
                                </div>
                            </div>

                            <div class="mt-3 border-t pt-3">
                                <div
                                    v-if="editing !== answer.id"
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="space-y-1 text-sm">
                                        <p>
                                            <span class="font-medium"
                                                >Nota:</span
                                            >
                                            {{ answer.score ?? '—' }} /
                                            {{ answer.question?.weight ?? '—' }}
                                        </p>
                                        <p
                                            v-if="answer.feedback"
                                            class="text-muted-foreground"
                                        >
                                            <span class="font-medium"
                                                >Feedback:</span
                                            >
                                            {{ answer.feedback }}
                                        </p>
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="startEdit(answer)"
                                    >
                                        Ajustar nota
                                    </Button>
                                </div>

                                <div v-else class="space-y-3">
                                    <div class="space-y-2">
                                        <label
                                            class="text-sm font-medium"
                                            :for="`score-${answer.id}`"
                                            >Nota (máx.
                                            {{
                                                answer.question?.weight
                                            }})</label
                                        >
                                        <Input
                                            :id="`score-${answer.id}`"
                                            type="number"
                                            :min="0"
                                            :max="answer.question?.weight ?? 0"
                                            step="0.01"
                                            v-model="answerForm.score"
                                        />
                                        <p
                                            v-if="answerForm.errors.score"
                                            class="text-sm text-destructive"
                                        >
                                            {{ answerForm.errors.score }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            class="text-sm font-medium"
                                            :for="`feedback-${answer.id}`"
                                            >Feedback</label
                                        >
                                        <Textarea
                                            :id="`feedback-${answer.id}`"
                                            v-model="answerForm.feedback"
                                            rows="2"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            size="sm"
                                            :disabled="answerForm.processing"
                                            @click="saveEdit(answer)"
                                        >
                                            <Spinner
                                                v-if="answerForm.processing"
                                                class="mr-2 size-4"
                                            />
                                            <CheckCircle2
                                                v-else
                                                class="mr-2 size-4"
                                            />
                                            Salvar
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="cancelEdit"
                                        >
                                            Cancelar
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <Dialog
        :open="cropAnswer !== null"
        @update:open="(value: boolean) => !value && (cropAnswer = null)"
    >
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>Recorte da resposta</DialogTitle>
            </DialogHeader>
            <ZoomableImage
                v-if="cropAnswer"
                :src="
                    submissions.answers.crop({
                        submission: submission.id,
                        answer: cropAnswer.id,
                    }).url
                "
                :alt="`Resposta da questão #${cropAnswer.question_id}`"
            />
        </DialogContent>
    </Dialog>
</template>
