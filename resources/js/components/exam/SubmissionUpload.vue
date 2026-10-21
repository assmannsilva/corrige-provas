<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Camera,
    FileText,
    Trash,
    Upload,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import exams from '@/routes/exams';

const props = defineProps<{
    examId: number;
}>();

type UploadItem = { file: File; preview?: string };

const form = useForm<{ files: File[]; student_name: string }>({
    files: [],
    student_name: '',
});
const nameModalOpen = ref(false);
const fileError = computed(
    () =>
        Object.entries(form.errors).find(
            ([field]) => field === 'files' || field.startsWith('files.'),
        )?.[1],
);

const items = ref<UploadItem[]>([]);

const video = ref<HTMLVideoElement | null>(null);
const stream = ref<MediaStream | null>(null);
const cameraModalOpen = ref(false);

const cameraSupported = computed(
    () =>
        'mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices,
);

const isImage = (file: File): boolean => file.type.startsWith('image/');

const syncForm = (): void => {
    form.files = items.value.map((item) => item.file);
};

const addFiles = (files: FileList | File[]): void => {
    for (const file of Array.from(files)) {
        const item: UploadItem = { file };

        if (isImage(file)) {
            item.preview = URL.createObjectURL(file);
        }

        items.value.push(item);
    }

    syncForm();
};

const onFileSelected = (event: Event): void => {
    const target = event.target as HTMLInputElement;

    if (target.files) {
        addFiles(target.files);
    }

    target.value = '';
};

const remove = (index: number): void => {
    const [removed] = items.value.splice(index, 1);

    if (removed?.preview) {
        URL.revokeObjectURL(removed.preview);
    }

    syncForm();
};

const move = (index: number, direction: -1 | 1): void => {
    const target = index + direction;

    if (target < 0 || target >= items.value.length) {
        return;
    }

    const list = items.value;
    [list[index], list[target]] = [list[target], list[index]];

    syncForm();
};

const startCamera = async (): Promise<void> => {
    if (!cameraSupported.value) {
        return;
    }

    cameraModalOpen.value = true;

    await nextTick();

    stream.value = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'environment',
            width: { ideal: 1440 },
            height: { ideal: 1920 },
            aspectRatio: { ideal: 0.75 },
        },
    });

    if (video.value) {
        video.value.srcObject = stream.value;
        await video.value.play();
    }
};

const stopCamera = (): void => {
    stream.value?.getTracks().forEach((track) => track.stop());
    stream.value = null;
};

const closeCamera = (): void => {
    stopCamera();
    cameraModalOpen.value = false;
};

watch(cameraModalOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

const capture = (): void => {
    const el = video.value;

    if (!el) {
        return;
    }

    const videoWidth = el.videoWidth;
    const videoHeight = el.videoHeight;

    if (!videoWidth || !videoHeight) {
        return;
    }

    const targetRatio = 3 / 4;

    let sourceX = 0;
    let sourceY = 0;
    let sourceWidth = videoWidth;
    let sourceHeight = videoHeight;

    if (videoWidth / videoHeight > targetRatio) {
        sourceWidth = videoHeight * targetRatio;
        sourceX = (videoWidth - sourceWidth) / 2;
    } else {
        sourceHeight = videoWidth / targetRatio;
        sourceY = (videoHeight - sourceHeight) / 2;
    }

    const canvas = document.createElement('canvas');
    canvas.width = sourceWidth;
    canvas.height = sourceHeight;

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    context.drawImage(
        el,
        sourceX,
        sourceY,
        sourceWidth,
        sourceHeight,
        0,
        0,
        sourceWidth,
        sourceHeight,
    );

    canvas.toBlob((blob) => {
        if (!blob) {
            return;
        }

        const file = new File([blob], `capture-${Date.now()}.png`, {
            type: 'image/png',
        });

        addFiles([file]);
        closeCamera();
    }, 'image/png');
};

const setNameModalOpen = (open: boolean): void => {
    if (!form.processing) {
        nameModalOpen.value = open;
    }
};

const submit = (): void => {
    if (form.processing || items.value.length === 0) {
        return;
    }

    form.student_name = form.student_name.trim();

    if (!form.student_name) {
        form.setError('student_name', 'Informe o nome do aluno.');

        return;
    }

    form.clearErrors();
    form.post(exams.submissions.store(props.examId).url, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
            if (!errors.student_name && fileError.value) {
                nameModalOpen.value = false;
            }
        },
        onSuccess: () => {
            items.value.forEach((item) => {
                if (item.preview) {
                    URL.revokeObjectURL(item.preview);
                }
            });

            items.value = [];
            form.reset();
            nameModalOpen.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-3">
        <input
            type="file"
            multiple
            accept="application/pdf,image/jpeg,image/png,image/webp"
            class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm file:font-medium"
            @change="onFileSelected"
        />

        <div v-if="cameraSupported">
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="startCamera"
            >
                <Camera class="mr-2 size-4" />
                Usar câmera
            </Button>

            <div
                v-if="cameraModalOpen"
                class="fixed inset-0 z-50 overflow-hidden bg-black"
            >
                <video
                    ref="video"
                    muted
                    playsinline
                    autoplay
                    class="absolute inset-0 h-full w-full object-contain"
                />

                <div
                    class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-3 p-4"
                    style="
                        padding-bottom: max(1rem, env(safe-area-inset-bottom));
                    "
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeCamera"
                    >
                        <X class="mr-2 size-4" />
                        Fechar
                    </Button>
                    <Button type="button" size="sm" @click="capture">
                        <Camera class="mr-2 size-4" />
                        Capturar página
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="items.length" class="space-y-2">
            <p class="text-xs text-muted-foreground">
                {{ items.length }} página(s) na ordem abaixo. Use as setas para
                reordenar.
            </p>

            <div
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-3 rounded-md border p-2"
            >
                <span
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground"
                >
                    {{ index + 1 }}
                </span>

                <img
                    v-if="item.preview"
                    :src="item.preview"
                    :alt="item.file.name"
                    class="h-28 w-20 shrink-0 rounded-md border bg-muted object-contain"
                />
                <div
                    v-else
                    class="flex h-28 w-20 shrink-0 items-center justify-center rounded-md border bg-muted"
                >
                    <FileText class="size-8 text-muted-foreground" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ item.file.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Página {{ index + 1 }} de {{ items.length }}
                    </p>
                </div>

                <div class="flex flex-col">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        @click="move(index, -1)"
                    >
                        <ArrowUp class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === items.length - 1"
                        @click="move(index, 1)"
                    >
                        <ArrowDown class="size-4" />
                    </Button>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    @click="remove(index)"
                >
                    <Trash class="size-4 text-destructive" />
                </Button>
            </div>
        </div>

        <InputError :message="fileError" />

        <Button
            type="button"
            :disabled="form.processing || items.length === 0"
            @click="setNameModalOpen(true)"
        >
            <Spinner v-if="form.processing" class="mr-2 size-4" />
            <Upload class="mr-2 size-4" />
            Enviar
        </Button>
        <Dialog :open="nameModalOpen" @update:open="setNameModalOpen">
            <DialogContent
                class="sm:max-w-md"
                :show-close-button="!form.processing"
            >
                <DialogHeader>
                    <DialogTitle>Identificar envio</DialogTitle>
                    <DialogDescription
                        >Confirme o nome do aluno ao qual todos os arquivos
                        selecionados pertencem. O nome fica no sistema e não é
                        enviado à IA.</DialogDescription
                    >
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="submission-student-name"
                            >Nome do aluno</Label
                        >
                        <Input
                            id="submission-student-name"
                            v-model="form.student_name"
                            required
                            maxlength="255"
                            autocomplete="off"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.student_name" />
                        <InputError :message="fileError" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="setNameModalOpen(false)"
                            >Cancelar</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                form.processing || !form.student_name.trim()
                            "
                        >
                            <Spinner
                                v-if="form.processing"
                                class="mr-2 size-4"
                            />
                            Confirmar envio
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
