<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PageHeader from '@/components/shared/PageHeader.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingSkeleton from '@/components/shared/LoadingSkeleton.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import ExamTable from '@/components/exam/ExamTable.vue';
import ExamCard from '@/components/exam/ExamCard.vue';
import { index as examsIndex } from '@/routes/exams';
import type { Exam } from '@/types';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exams: {
        data: Exam[];
        current_page: number;
        last_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters?: {
        search?: string;
        status?: string;
    };
}>();

const page = usePage();

const breadcrumbs = [
    { title: 'Início', href: examsIndex() },
    { title: 'Provas', href: examsIndex() },
];

const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? 'all');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const onSearch = (value: string) => {
    search.value = value;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(examsIndex(), {
            search: value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
};

const onStatusChange = (value: string) => {
    statusFilter.value = value;
    router.get(examsIndex(), {
        search: search.value || undefined,
        status: value !== 'all' ? value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const deleteTarget = ref<Exam | null>(null);
const deleting = ref(false);

const openDelete = (exam: Exam) => {
    deleteTarget.value = exam;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/exams/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};

const isLoading = computed(() => page.props.errors != null && Object.keys(page.props.errors).length > 0);
</script>

<template>
    <Head title="Provas" />

    <div class="p-4 md:p-6">
        <PageHeader
            :breadcrumbs="breadcrumbs"
            title="Provas"
            description="Gerencie suas provas e crie novas."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="'/exams/create'">
                        <Plus class="mr-2 size-4" />
                        Nova Prova
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    :model-value="search"
                    placeholder="Buscar provas..."
                    class="pl-8"
                    @update:model-value="onSearch"
                />
            </div>
            <Select :model-value="statusFilter" @update:model-value="onStatusChange">
                <SelectTrigger class="w-full sm:w-36">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Todas</SelectItem>
                    <SelectItem value="draft">Rascunho</SelectItem>
                    <SelectItem value="published">Publicada</SelectItem>
                    <SelectItem value="archived">Arquivada</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="mt-4">
            <LoadingSkeleton v-if="isLoading" variant="table" :rows="5" />

            <EmptyState
                v-else-if="exams.data.length === 0 && !search && statusFilter === 'all'"
                title="Nenhuma prova ainda"
                description="Crie sua primeira prova para começar."
                action-label="Nova Prova"
                action-href="/exams/create"
            />

            <EmptyState
                v-else-if="exams.data.length === 0"
                title="Nenhuma prova encontrada"
                description="Nenhuma prova corresponde à sua busca."
            />

            <template v-else>
                <ExamTable
                    v-if="$page.props.exams"
                    :exams="exams.data"
                    class="hidden md:block"
                    @delete="openDelete"
                />

                <ExamCard
                    v-if="$page.props.exams"
                    :exams="exams.data"
                    class="md:hidden"
                    @delete="openDelete"
                />

                <div
                    v-if="exams.last_page > 1"
                    class="mt-4 flex items-center justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        Mostrando {{ exams.from }} a {{ exams.to }} de {{ exams.total }}
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in exams.links"
                            :key="link.label"
                            variant="ghost"
                            size="sm"
                            :disabled="!link.url"
                            :class="{ 'bg-muted': link.active }"
                            as-child
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                            >
                                <span v-html="link.label" />
                            </Link>
                            <span v-else v-html="link.label" />
                        </Button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <ConfirmDialog
        :open="deleteTarget !== null"
        title="Excluir Prova"
        :description="`Tem certeza que deseja excluir '${deleteTarget?.title}'? Essa ação não pode ser desfeita.`"
        confirm-label="Excluir"
        variant="destructive"
        :processing="deleting"
        @confirm="confirmDelete"
        @cancel="deleteTarget = null"
    />
</template>
