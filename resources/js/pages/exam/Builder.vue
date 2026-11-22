<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Save, Send } from '@lucide/vue';
import { ref, computed } from 'vue';
import ExamDetailsForm from '@/components/exam/ExamDetailsForm.vue';
import ExamStatusBadge from '@/components/exam/ExamStatusBadge.vue';
import QuestionForm from '@/components/exam/QuestionForm.vue';
import QuestionRow from '@/components/exam/QuestionRow.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import type { Exam, Question, QuestionTypeDefinition } from '@/types';
import { index as examsIndex } from '@/routes/exams';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: Exam | null;
    questionTypes: QuestionTypeDefinition[];
}>();

const isCreate = computed(() => !props.exam);

const breadcrumbs = computed(() => {
    const base = [
        { title: 'Início', href: examsIndex() },
        { title: 'Provas', href: examsIndex() },
    ];

    if (isCreate.value) {
        return [...base, { title: 'Nova Prova', href: '/exams/create' }];
    }

    return [
        ...base,
        { title: props.exam!.title, href: `/exams/${props.exam!.id}` },
        { title: 'Editar', href: `/exams/${props.exam!.id}/edit` },
    ];
});

const typeLabel = (identifier: string): string => {
    return (
        props.questionTypes.find((qt) => qt.identifier === identifier)?.label ??
        identifier
    );
};

const examForm = useForm({
    title: props.exam?.title ?? '',
    instructions: props.exam?.instructions ?? '',
});

const saveExam = () => {
    if (isCreate.value) {
        examForm.post('/exams');
    } else {
        examForm.put(`/exams/${props.exam!.id}`, {
            preserveScroll: true,
        });
    }
};

const publishConfirmOpen = ref(false);

const publishExam = () => {
    publishConfirmOpen.value = false;
    examForm
        .transform((data) => ({ ...data, status: 'published' }))
        .put(`/exams/${props.exam!.id}`, {
            preserveScroll: true,
        });
};

const showingForm = ref(false);
const editingQuestion = ref<Question | null>(null);

const questionForm = useForm({
    type: '',
    statement: '',
    weight: 1,
    grading_notes: '',
    metadata: {} as Record<string, unknown>,
});

const openAddForm = () => {
    editingQuestion.value = null;
    questionForm.reset();
    questionForm.clearErrors();
    const typeIdentifier = props.questionTypes[0]?.identifier ?? '';
    questionForm.type = typeIdentifier;
    const typeDef = props.questionTypes.find(
        (qt) => qt.identifier === typeIdentifier,
    );
    questionForm.metadata = { ...(typeDef?.default_metadata ?? {}) };
    showingForm.value = true;
};

const openEditForm = (question: Question) => {
    editingQuestion.value = question;
    questionForm.reset();
    questionForm.clearErrors();
    questionForm.type = question.type;
    questionForm.statement = question.statement;
    questionForm.weight = question.weight;
    questionForm.grading_notes = question.grading_notes ?? '';
    questionForm.metadata = { ...(question.metadata ?? {}) };
    showingForm.value = true;
};

const closeForm = () => {
    showingForm.value = false;
    editingQuestion.value = null;
    questionForm.reset();
    questionForm.clearErrors();
};

const submitQuestion = () => {
    if (editingQuestion.value) {
        questionForm.put(
            `/exams/${props.exam!.id}/questions/${editingQuestion.value.id}`,
            {
                preserveScroll: true,
                onSuccess: () => closeForm(),
            },
        );
    } else {
        questionForm.post(`/exams/${props.exam!.id}/questions`, {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    }
};

const onTypeChange = (value: string) => {
    questionForm.type = value;
    const typeDef = props.questionTypes.find((qt) => qt.identifier === value);
    questionForm.metadata = { ...(typeDef?.default_metadata ?? {}) };

    if (value !== 'essay') {
        questionForm.grading_notes = '';
    }
};

const moveUp = (question: Question) => {
    const questions = [...(props.exam?.questions ?? [])];
    const idx = questions.findIndex((q) => q.id === question.id);

    if (idx <= 0) {
        return;
    }

    [questions[idx - 1], questions[idx]] = [questions[idx], questions[idx - 1]];
    const orderedIds = questions.map((q) => q.id);
    useForm({ ordered_ids: orderedIds }).post(
        `/exams/${props.exam!.id}/questions/reorder`,
        { preserveScroll: true },
    );
};

const moveDown = (question: Question) => {
    const questions = [...(props.exam?.questions ?? [])];
    const idx = questions.findIndex((q) => q.id === question.id);

    if (idx < 0 || idx >= questions.length - 1) {
        return;
    }

    [questions[idx], questions[idx + 1]] = [questions[idx + 1], questions[idx]];
    const orderedIds = questions.map((q) => q.id);
    useForm({ ordered_ids: orderedIds }).post(
        `/exams/${props.exam!.id}/questions/reorder`,
        { preserveScroll: true },
    );
};

const deleteTarget = ref<Question | null>(null);
const deleting = ref(false);

const openDelete = (question: Question) => {
    deleteTarget.value = question;
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    deleting.value = true;
    useForm({}).delete(
        `/exams/${props.exam!.id}/questions/${deleteTarget.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                deleteTarget.value = null;
            },
        },
    );
};

const hasQuestions = computed(() => (props.exam?.questions?.length ?? 0) > 0);
</script>

<template>
    <Head :title="isCreate ? 'Criar Prova' : `Editar - ${exam?.title}`" />

    <div class="p-4 md:p-6">
        <PageHeader
            :breadcrumbs="breadcrumbs"
            :title="isCreate ? 'Nova Prova' : (exam?.title ?? 'Editar Prova')"
            :description="
                isCreate
                    ? 'Configure a prova e adicione questões.'
                    : 'Configure a prova e gerencie as questões.'
            "
        >
            <template v-if="!isCreate" #actions>
                <ExamStatusBadge :status="exam!.status" />
            </template>
        </PageHeader>

        <div class="mt-4 space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Detalhes da Prova</CardTitle>
                    <CardDescription
                        >Informações básicas sobre a prova.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="saveExam">
                        <ExamDetailsForm
                            :title="examForm.title"
                            :instructions="examForm.instructions"
                            :errors="examForm.errors as Record<string, string>"
                            @update:title="examForm.title = $event"
                            @update:instructions="
                                examForm.instructions = $event
                            "
                        />
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                :disabled="examForm.processing"
                            >
                                <Spinner
                                    v-if="examForm.processing"
                                    class="mr-2 size-4"
                                />
                                <Save class="mr-2 size-4" />
                                Salvar
                            </Button>
                            <Button
                                v-if="
                                    !isCreate &&
                                    hasQuestions &&
                                    exam?.status === 'draft'
                                "
                                type="button"
                                variant="default"
                                @click="publishConfirmOpen = true"
                            >
                                <Send class="mr-2 size-4" />
                                Publicar
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card v-if="!isCreate">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Questões</CardTitle>
                            <CardDescription>
                                {{ exam?.questions?.length ?? 0 }} questão(ões)
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="exam?.questions?.length" class="space-y-2">
                        <template
                            v-for="(question, idx) in exam.questions"
                            :key="question.id"
                        >
                            <QuestionRow
                                :question="question"
                                :type-label="typeLabel(question.type)"
                                :is-first="idx === 0"
                                :is-last="
                                    idx === (exam.questions?.length ?? 0) - 1
                                "
                                @edit="openEditForm"
                                @move-up="moveUp"
                                @move-down="moveDown"
                                @delete="openDelete"
                            />
                            <QuestionForm
                                v-if="
                                    showingForm &&
                                    editingQuestion?.id === question.id
                                "
                                :question-types="questionTypes"
                                :type="questionForm.type"
                                :statement="questionForm.statement"
                                :weight="questionForm.weight"
                                :grading-notes="questionForm.grading_notes"
                                :metadata="questionForm.metadata"
                                :errors="
                                    questionForm.errors as Record<
                                        string,
                                        string
                                    >
                                "
                                :processing="questionForm.processing"
                                submit-label="Salvar Questão"
                                lock-type
                                @update:type="onTypeChange"
                                @update:statement="
                                    questionForm.statement = $event
                                "
                                @update:weight="questionForm.weight = $event"
                                @update:grading-notes="
                                    questionForm.grading_notes = $event
                                "
                                @update:metadata="
                                    questionForm.metadata = $event
                                "
                                @submit="submitQuestion"
                                @cancel="closeForm"
                            />
                        </template>
                    </div>

                    <p
                        v-else
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Nenhuma questão ainda.
                    </p>

                    <div v-if="!showingForm">
                        <Button
                            variant="outline"
                            class="w-full border-dashed"
                            @click="openAddForm"
                        >
                            <Plus class="mr-2 size-4" />
                            Adicionar Questão
                        </Button>
                    </div>

                    <QuestionForm
                        v-if="showingForm && editingQuestion === null"
                        :question-types="questionTypes"
                        :type="questionForm.type"
                        :statement="questionForm.statement"
                        :weight="questionForm.weight"
                        :grading-notes="questionForm.grading_notes"
                        :metadata="questionForm.metadata"
                        :errors="questionForm.errors as Record<string, string>"
                        :processing="questionForm.processing"
                        submit-label="Adicionar Questão"
                        @update:type="onTypeChange"
                        @update:statement="questionForm.statement = $event"
                        @update:weight="questionForm.weight = $event"
                        @update:grading-notes="
                            questionForm.grading_notes = $event
                        "
                        @update:metadata="questionForm.metadata = $event"
                        @submit="submitQuestion"
                        @cancel="closeForm"
                    />
                </CardContent>
            </Card>
        </div>
    </div>

    <ConfirmDialog
        :open="publishConfirmOpen"
        title="Publicar Prova"
        description="Após publicar, os alunos poderão enviar respostas. Você não poderá editar as questões após a publicação."
        confirm-label="Publicar"
        variant="default"
        @confirm="publishExam"
        @cancel="publishConfirmOpen = false"
    />

    <ConfirmDialog
        :open="deleteTarget !== null"
        title="Excluir Questão"
        :description="`Tem certeza que deseja remover esta questão? Essa ação não pode ser desfeita.`"
        confirm-label="Excluir"
        variant="destructive"
        :processing="deleting"
        @confirm="confirmDelete"
        @cancel="deleteTarget = null"
    />
</template>
