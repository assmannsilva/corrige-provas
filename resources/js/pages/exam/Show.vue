<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    MoreHorizontal,
    Pencil,
    Send,
    Archive,
    Trash,
    Download,
    FileText,
    Files,
} from '@lucide/vue';
import { ref } from 'vue';
import ExamStatusBadge from '@/components/exam/ExamStatusBadge.vue';
import QuestionPreview from '@/components/exam/QuestionPreview.vue';
import SubmissionUpload from '@/components/exam/SubmissionUpload.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Exam, QuestionTypeDefinition } from '@/types';
import exams, { index as examsIndex } from '@/routes/exams';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: Exam;
    questionTypes: QuestionTypeDefinition[];
}>();

const breadcrumbs = [
    { title: 'Início', href: examsIndex() },
    { title: 'Provas', href: examsIndex() },
    { title: props.exam.title, href: `/exams/${props.exam.id}` },
];

const typeLabel = (identifier: string): string => {
    return (
        props.questionTypes.find((qt) => qt.identifier === identifier)?.label ??
        identifier
    );
};

const publishConfirmOpen = ref(false);
const archiveConfirmOpen = ref(false);
const deleteConfirmOpen = ref(false);
const processing = ref(false);

const publishExam = () => {
    publishConfirmOpen.value = false;
    processing.value = true;
    router.put(
        `/exams/${props.exam.id}`,
        {
            status: 'published',
        },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const archiveExam = () => {
    archiveConfirmOpen.value = false;
    processing.value = true;
    router.put(
        `/exams/${props.exam.id}`,
        {
            status: 'archived',
        },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const deleteExam = () => {
    deleteConfirmOpen.value = false;
    processing.value = true;
    router.delete(`/exams/${props.exam.id}`, {
        onFinish: () => {
            processing.value = false;
        },
    });
};

const generatePdfForm = useForm({});

const generatePdf = () => {
    generatePdfForm.post(exams.pdf.generate(props.exam.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.href = exams.pdf.download(props.exam.id).url;
        },
    });
};
</script>

<template>
    <Head :title="exam.title" />

    <div class="p-4 md:p-6">
        <PageHeader
            :breadcrumbs="breadcrumbs"
            :title="exam.title"
            description="Revise os detalhes e as questões da prova."
        >
            <template #actions>
                <ExamStatusBadge :status="exam.status" />
                <Button
                    v-if="exam.status === 'draft'"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="`/exams/${exam.id}/edit`">
                        <Pencil class="mr-2 size-4" />
                        Editar
                    </Link>
                </Button>
                <Button
                    v-if="
                        exam.status === 'draft' &&
                        (exam.questions?.length ?? 0) > 0
                    "
                    size="sm"
                    @click="publishConfirmOpen = true"
                >
                    <Send class="mr-2 size-4" />
                    Publicar
                </Button>
                <Button
                    v-if="exam.status === 'published' && !exam.pdf_ready"
                    size="sm"
                    variant="outline"
                    :disabled="generatePdfForm.processing"
                    @click="generatePdf"
                >
                    <FileText class="mr-2 size-4" />
                    Gerar PDF
                </Button>
                <Button
                    v-if="exam.pdf_ready"
                    size="sm"
                    variant="outline"
                    as-child
                >
                    <a :href="exams.pdf.download(exam.id).url">
                        <Download class="mr-2 size-4" />
                        Baixar PDF
                    </a>
                </Button>
                <Button
                    v-if="exam.status === 'published'"
                    size="sm"
                    variant="outline"
                    as-child
                >
                    <Link :href="exams.submissions.index(exam.id).url">
                        <Files class="mr-2 size-4" />
                        Envios
                    </Link>
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm">
                            <MoreHorizontal class="mr-2 size-4" />
                            Mais
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            v-if="exam.status !== 'archived'"
                            @click="archiveConfirmOpen = true"
                        >
                            <Archive class="mr-2 size-4" />
                            Arquivar
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-if="exam.status === 'draft'"
                            class="text-destructive"
                            @click="deleteConfirmOpen = true"
                        >
                            <Trash class="mr-2 size-4" />
                            Excluir
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>
        </PageHeader>

        <div class="mt-4 space-y-4">
            <Card v-if="exam.instructions">
                <CardHeader>
                    <CardTitle class="text-base">Instruções</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        class="text-sm whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ exam.instructions }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">Questões</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                {{ exam.questions?.length ?? 0 }} questão(ões) ·
                                {{ exam.total_points }} pontos no total
                            </p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!exam.questions?.length"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Nenhuma questão foi adicionada a esta prova ainda.
                    </p>
                    <div v-else class="space-y-3">
                        <QuestionPreview
                            v-for="question in exam.questions"
                            :key="question.id"
                            :question="question"
                            :type-label="typeLabel(question.type)"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card v-if="exam.status === 'published'">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Enviar Prova</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        Envie todos os arquivos de um único aluno por vez (PDF
                        ou foto). Você vai informar o nome antes de confirmar o
                        envio.
                    </p>
                </CardHeader>
                <CardContent>
                    <SubmissionUpload :exam-id="exam.id" />
                </CardContent>
            </Card>
        </div>
    </div>

    <ConfirmDialog
        :open="publishConfirmOpen"
        title="Publicar Prova"
        description="Após publicar, você não poderá editar as questões. Os alunos poderão enviar respostas."
        confirm-label="Publicar"
        variant="default"
        @confirm="publishExam"
        @cancel="publishConfirmOpen = false"
    />

    <ConfirmDialog
        :open="archiveConfirmOpen"
        title="Arquivar Prova"
        description="Provas arquivadas ficam ocultas da lista principal, mas ainda podem ser acessadas."
        confirm-label="Arquivar"
        @confirm="archiveExam"
        @cancel="archiveConfirmOpen = false"
    />

    <ConfirmDialog
        :open="deleteConfirmOpen"
        title="Excluir Prova"
        :description="`Tem certeza que deseja excluir '${exam.title}'? Essa ação não pode ser desfeita.`"
        confirm-label="Excluir"
        variant="destructive"
        @confirm="deleteExam"
        @cancel="deleteConfirmOpen = false"
    />
</template>
