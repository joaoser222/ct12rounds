import { ref } from 'vue';

type ConfirmPayload = {
    id?: number;
    title?: string;
    message: string;
};

const queue = ref<ConfirmPayload[]>([]);
const resolve = ref<((value: boolean) => void) | null>(null);
let nextId = 1;

export function useConfirm() {
    function confirm(title: string, message: string): Promise<boolean> {
        const id = nextId++;

        return new Promise<boolean>((res) => {
            resolve.value = res;
            queue.value.push({ id, title, message });
        });
    }

    function handleConfirm(): void {
        const pending = queue.value[0];
        if (pending) {
            queue.value.shift();
        }
        resolve.value?.(true);
        resolve.value = null;
    }

    function handleCancel(): void {
        const pending = queue.value[0];
        if (pending) {
            queue.value.shift();
        }
        resolve.value?.(false);
        resolve.value = null;
    }

    return {
        queue,
        confirm,
        handleConfirm,
        handleCancel,
    };
}
