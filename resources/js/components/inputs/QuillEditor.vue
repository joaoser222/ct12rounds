<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Quill from 'quill';

const props = defineProps<{
    modelValue?: string;
    label?: string;
    placeholder?: string;
    rows?: number;
    errorMessages?: string | string[];
    readonly?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLDivElement | null>(null);
const quillInstance = ref<Quill | null>(null);
const isFocused = ref(false);

onMounted(() => {
    if (!editorRef.value) return;

    quillInstance.value = new Quill(editorRef.value, {
        theme: 'snow',
        placeholder: props.placeholder ?? 'Digite sua mensagem...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote'],
                ['clean'],
            ],
        },
        readonly: props.readonly ?? false,
    });

    if (props.modelValue) {
        quillInstance.value.root.innerHTML = props.modelValue;
    }

    quillInstance.value.on('text-change', () => {
        if (!quillInstance.value) return;
        emit('update:modelValue', quillInstance.value.root.innerHTML);
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (!quillInstance.value) return;
        if (quillInstance.value.root.innerHTML !== value) {
            quillInstance.value.root.innerHTML = value ?? '';
        }
    },
);

watch(
    () => props.readonly,
    (value) => {
        if (!quillInstance.value) return;
        quillInstance.value.enable(!value);
    },
);

onBeforeUnmount(() => {
    quillInstance.value = null;
});
</script>

<template>
    <div class="v-input v-input--density-compact v-input--variant-outlined v-textarea">
        <div class="v-field v-field--variant-outlined v-field--active" :class="{ 'v-field--focused': isFocused }">
            <label v-if="label" class="v-label v-field-label">{{ label }}</label>
            <div class="v-field__input quill-editor-wrapper" :style="{ minHeight: `${(rows ?? 3) * 24 + 32}px` }">
                <div ref="editorRef" class="quill-editor" />
            </div>
        </div>
        <div v-if="errorMessages" class="v-messages">
            <span class="v-messages__message">{{ Array.isArray(errorMessages) ? errorMessages[0] : errorMessages }}</span>
        </div>
    </div>
</template>

<style>
@import 'quill/dist/quill.snow.css';

.quill-editor-wrapper {
    padding: 0;
}

.quill-editor .ql-toolbar.ql-snow {
    border: none;
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding: 4px 8px;
    background-color: rgba(var(--v-theme-surface), 0.5);
}

.quill-editor .ql-container.ql-snow {
    border: none;
    font-family: inherit;
    font-size: inherit;
    color: inherit;
}

.quill-editor .ql-editor {
    padding: 12px 16px;
    min-height: 100%;
    line-height: 1.5;
}

.quill-editor .ql-editor.ql-blank::before {
    color: rgba(var(--v-theme-on-surface), 0.5);
    font-style: normal;
}

.quill-editor .ql-snow .ql-stroke {
    stroke: rgba(var(--v-theme-on-surface), 0.7);
}

.quill-editor .ql-snow .ql-fill {
    fill: rgba(var(--v-theme-on-surface), 0.7);
}

.quill-editor .ql-snow .ql-picker {
    color: rgba(var(--v-theme-on-surface), 0.7);
}

.quill-editor .ql-snow .ql-picker-options {
    background-color: rgb(var(--v-theme-surface));
    border-color: rgba(var(--v-border-color), var(--v-border-opacity));
}

.quill-editor .ql-snow .ql-active .ql-stroke {
    stroke: rgb(var(--v-theme-primary));
}

.quill-editor .ql-snow .ql-active .ql-fill {
    fill: rgb(var(--v-theme-primary));
}

.quill-editor .ql-snow .ql-active {
    color: rgb(var(--v-theme-primary));
}

.v-field {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 56px;
    border-radius: 4px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    background-color: rgb(var(--v-theme-surface));
    transition: border-color 0.2s ease;
}

.v-field--focused {
    border-color: rgb(var(--v-theme-primary));
    border-width: 2px;
}

.v-label {
    position: absolute;
    left: 12px;
    top: -8px;
    padding: 0 4px;
    background-color: rgb(var(--v-theme-surface));
    font-size: 12px;
    color: rgba(var(--v-theme-on-surface), 0.7);
    z-index: 1;
}

.v-field--focused .v-label {
    color: rgb(var(--v-theme-primary));
}

.v-field__input {
    display: flex;
    flex-direction: column;
}

.v-messages {
    font-size: 12px;
    padding-left: 16px;
    padding-top: 4px;
    color: rgb(var(--v-theme-error));
}
</style>
