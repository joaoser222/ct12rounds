<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import type { Editor } from 'grapesjs';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';
import type { LandingTemplateSeed } from './landingTemplate';
import { buildLandingTemplate } from './landingTemplate';

defineOptions({ layout: null });

const props = defineProps<{
    project?: unknown | null;
    status?: string;
    publishedAt?: string | null;
    draftHtml?: string | null;
    templateSeed?: LandingTemplateSeed;
    publishUrl: string;
    contentUrl: string;
    imageUploadUrl: string;
    viewUrl: string;
}>();

const editorMount = ref<HTMLDivElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

let editor: Editor | null = null;
let saveTimer: ReturnType<typeof setTimeout> | null = null;

const statusLabel = ref(
    props.status === 'published' ? 'Publicado' : 'Rascunho',
);
const editorReady = ref(false);
const publishedAt = ref(props.publishedAt ?? null);
const saving = ref(false);
const publishing = ref(false);

const { confirm } = useConfirm();
const { show } = useToast();

onMounted(async () => {
    const grapesjs = (await import('grapesjs')).default;
    const presetWebpage = (await import('grapesjs-preset-webpage')).default;

    await import('grapesjs/dist/css/grapes.min.css');

    if (editorMount.value === null) {
        return;
    }

    editor = grapesjs.init({
        container: editorMount.value,
        fromElement: false,
        height: '100%',
        width: '100%',
        storageManager: false,
        assetManager: {
            storageType: 'none',
            autoAdd: true,
        },
        telemetry: false,
        plugins: [presetWebpage],
    });

    if (props.project !== null && props.project !== undefined && Object.keys(props.project as object).length > 0) {
        editor.loadProjectData(props.project);
    } else {
        const seed = buildLandingTemplate({
            subtitle: props.templateSeed?.subtitle ?? '',
            ctaText: props.templateSeed?.ctaText ?? 'Começar agora',
            whatsappUrl: props.templateSeed?.whatsappUrl ?? '',
        });

        editor.setComponents(seed.html);
        editor.setStyle(seed.css);
    }

    editor.on('update', scheduleSave);
    editorReady.value = true;
});

const scheduleSave = () => {
    if (saveTimer !== null) {
        clearTimeout(saveTimer);
    }

    saveTimer = setTimeout(() => {
        void saveDraft(true);
    }, 1500);
};

const saveDraft = async (auto = false) => {
    if (editor === null) {
        return;
    }

    saving.value = true;

    try {
        await axios.put(props.contentUrl, {
            project: editor.getProjectData(),
            html: editor.getHtml(),
            css: editor.getCss(),
        });

        statusLabel.value = 'Rascunho';

        if (!auto) {
            show({ type: 'success', message: 'Rascunho salvo com sucesso.' });
        }
    } catch {
        if (!auto) {
            show({ type: 'error', message: 'Falha ao salvar o rascunho.' });
        }
    } finally {
        saving.value = false;
    }
};

const publish = async () => {
    const accepted = await confirm(
        'Publicar landing',
        'O conteúdo atual substituirá a versão publicada do site. Deseja continuar?',
    );

    if (!accepted || editor === null) {
        return;
    }

    publishing.value = true;

    try {
        await saveDraft(true);

        const { data } = await axios.post(props.publishUrl);

        publishedAt.value = data.publishedAt ?? null;
        statusLabel.value = 'Publicado';
        show({ type: 'success', message: 'Landing publicada com sucesso!' });
    } catch {
        show({ type: 'error', message: 'Falha ao publicar a landing.' });
    } finally {
        publishing.value = false;
    }
};

const openUpload = () => {
    fileInput.value?.click();
};

const onFilesSelected = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    input.value = '';

    if (file === undefined || editor === null) {
        return;
    }

    const payload = new FormData();
    payload.append('file', file);

    try {
        const { data } = await axios.post(props.imageUploadUrl, payload);

        editor.AssetManager.add({ src: data.url, name: file.name });
        editor.AssetManager.open();

        show({ type: 'success', message: 'Imagem enviada.' });
    } catch {
        show({ type: 'error', message: 'Falha no upload da imagem.' });
    }
};

const logout = () => {
    router.post('/landing-admin/logout');
};

onBeforeUnmount(() => {
    if (saveTimer !== null) {
        clearTimeout(saveTimer);
    }

    editor?.destroy();
    editor = null;
});
</script>

<template>
    <div class="landing-editor">
        <header class="landing-editor__bar">
            <div class="landing-editor__title">
                <v-icon icon="ti ti-brush" class="mr-2" />
                <span class="text-body-2 font-weight-bold">Editor da Landing</span>
            </div>

            <v-chip
                :color="statusLabel === 'Publicado' ? 'success' : 'warning'"
                variant="tonal"
                size="small"
                class="mr-4"
            >
                {{ statusLabel }}
            </v-chip>

            <div class="flex-grow-1" />

            <v-btn
                variant="text"
                prepend-icon="ti ti-external-link"
                :href="viewUrl"
                target="_blank"
                size="small"
                class="text-white"
            >
                Ver site
            </v-btn>
            <v-btn
                variant="outlined"
                prepend-icon="ti ti-upload"
                class="mr-2 text-white"
                size="small"
                @click="openUpload"
            >
                Imagem
            </v-btn>
            <v-btn
                color="primary"
                variant="flat"
                prepend-icon="ti ti-device-floppy"
                class="mr-2"
                size="small"
                :loading="saving"
                :disabled="saving || !editorReady"
                @click="saveDraft(false)"
            >
                Salvar rascunho
            </v-btn>
            <v-btn
                color="success"
                variant="flat"
                prepend-icon="ti ti-player-play"
                class="mr-2"
                size="small"
                :loading="publishing"
                :disabled="publishing || !editorReady"
                @click="publish"
            >
                Publicar
            </v-btn>
            <v-btn
                variant="text"
                prepend-icon="ti ti-logout"
                size="small"
                class="text-white"
                @click="logout"
            >
                Sair
            </v-btn>
        </header>

        <div ref="editorMount" class="landing-editor__mount" />
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            hidden
            @change="onFilesSelected"
        />
    </div>
</template>

<style scoped>
.landing-editor {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

.landing-editor__bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    background: #121212;
    color: #fff;
    padding: 12px 16px;
}

.landing-editor__title {
    display: flex;
    align-items: center;
    margin-right: 8px;
}

.landing-editor__mount {
    flex: 1;
    min-height: 0;
}
</style>
