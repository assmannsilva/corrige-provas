import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

type HttpResponse = {
    status: number;
    data: unknown;
    headers: Record<string, string>;
};

const fallbackMessage =
    'Ação não permitida. Verifique as condições da prova e tente novamente.';

const messageFor = (response: HttpResponse): string => {
    if (
        response.data !== null &&
        typeof response.data === 'object' &&
        'message' in response.data &&
        typeof response.data.message === 'string' &&
        response.data.message
    ) {
        return response.data.message;
    }

    return fallbackMessage;
};

export function initializeHttpExceptionToast(): void {
    router.on('httpException', (event) => {
        const response = (event as CustomEvent<{ response: HttpResponse }>)
            .detail?.response;

        if (!response || response.status !== 403) {
            return;
        }

        toast.error(messageFor(response));

        return false;
    });
}
