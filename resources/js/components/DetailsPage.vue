<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
    useModulePermissions,
    type ModulePermissionMap,
} from '@/composables/useModulePermissions';
import type { DetailsRoutes } from '@/shared/page';

/**
 * Generic wrapper for creation/editing screens.
 *
 * Responsibilities:
 * - build the `useForm` from `defaults` + `item`;
 * - automatically switch between store and update;
 * - control permission, validation, and button state;
 * - expose the form via slot for the page that owns the fields.
 */

// The generic form automatically distinguishes between creation and update permissions.
type DetailsPermissionAction = 'create' | 'update';
type DetailsPermissionMap = ModulePermissionMap<DetailsPermissionAction>;

type FormData = Record<string, any>;
type VForm = {
    validate: () => Promise<{ valid: boolean }>;
    resetValidation: () => void;
};

// The page that owns the form provides data, routes, and defaults; this component handles the editing cycle.
const props = withDefaults(
    defineProps<{
        title: string;
        item?: FormData | null;
        defaults?: FormData;
        routes: DetailsRoutes;
        itemKey?: string;
        saveLabel?: string;
        cancelLabel?: string;
        module?: string;
        permissions?: string[];
        permissionMap?: DetailsPermissionMap;
        hideSaveAction?: boolean;
        canSaveOverride?: boolean | null;
    }>(),
    {
        item: null,
        defaults: () => ({}),
        itemKey: 'id',
        saveLabel: 'Salvar',
        cancelLabel: 'Voltar',
        hideSaveAction: false,
        canSaveOverride: null,
    },
);

const formDetails = ref<VForm | null>(null);
const formState = ref({ valid: null as boolean | null, validated: false });
const { hasPermission, ensurePermissionsLoaded } =
    useModulePermissions<DetailsPermissionAction>({
        module: () => props.module,
        permissions: () => props.permissions,
        permissionMap: () => props.permissionMap,
    });

const initialData = computed<FormData>(() => ({
    ...props.defaults,
    ...(props.item ?? {}),
}));

// `useForm` maintains integration with Inertia's validation/errors without coupling the schema to the generic component.
const form = useForm<FormData>({ ...initialData.value });

const formErrors = computed(() => form.errors);

const emit = defineEmits<{
    save: [form: typeof form];
    cancel: [];
}>();

// Without an identifier, the component assumes the creation flow.
const recordId = computed(() => props.item?.[props.itemKey]);
const isCreating = computed(
    () => recordId.value === undefined || recordId.value === null,
);
const pageTitle = computed(
    () => `${isCreating.value ? 'Criar' : 'Editar'} ${props.title}`,
);

const permissions = computed(() => ({
    submit: isCreating.value ? hasPermission('create') : hasPermission('update'),
}));

const canSubmit = computed(
    () => permissions.value.submit && props.canSaveOverride !== false,
);

const canSave = computed(
    () =>
        canSubmit.value &&
        formState.value.validated &&
        formState.value.valid === true &&
        !form.processing,
);

const validate = async (): Promise<boolean> => {
    if (!formDetails.value) {
        formState.value.validated = false;
        formState.value.valid = false;

        return false;
    }

    const result = await formDetails.value.validate();

    formState.value.validated = true;
    formState.value.valid = result.valid;

    return result.valid;
};

// The submit automatically chooses between creation and update based on the presence of the identifier.
const submit = async (overrides: FormData = {}): Promise<void> => {
    if (!canSubmit.value) {
        return;
    }

    if (!(await validate())) {
        return;
    }

    const payload = {
        ...form.data(),
        ...overrides,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('save', form),
        onFinish: () => form.transform((data) => data),
    };

    if (isCreating.value) {
        if (props.routes.store) {
            form.transform(() => payload).post(props.routes.store, options);
        }

        return;
    }

    const updateRoute = props.routes.update?.replace(':id', String(recordId.value));

    if (updateRoute) {
        form.transform(() => payload).put(updateRoute, options);
    }
};

const cancel = (): void => {
    emit('cancel');

    if (props.routes.index) {
        router.visit(props.routes.index);
    }
};

watch(
    initialData,
    (data) => {
        // Whenever the item changes, the form returns to the base state of that edit.
        form.defaults({ ...data });
        form.reset();
        formDetails.value?.resetValidation();
        formState.value.validated = false;
        formState.value.valid = null;
        void nextTick(validate);
    },
    { deep: true },
);

watch(
    () => form.data(),
    (current, previous) => {
        // Clears the error of the field the user just edited.
        for (const key of Object.keys(current)) {
            if (key in previous && current[key] !== previous[key] && form.errors[key]) {
                form.clearErrors(key);
            }
        }

        // Revalidates asynchronously to keep the button state consistent with the current form.
        void nextTick(validate);
    },
    { deep: true },
);

onMounted(() => {
    void ensurePermissionsLoaded();
});

void nextTick(validate);
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4">
            <div>
                <h1 class="text-h5 font-weight-medium">{{ pageTitle }}</h1>
            </div>
        </div>

        <v-card>
            <v-card-text>
                <v-form
                    ref="formDetails"
                    v-model="formState.valid"
                    validate-on="input"
                    @submit.prevent="submit"
                >
                    <!-- The page consumes the ready form and renders only the module-specific fields. -->
                    <slot
                        :form="form"
                        :errors="formErrors"
                        :is-creating="isCreating"
                        :validate="validate"
                        :canSubmit="canSubmit"
                        :readonly="!canSubmit"
                        :submit="submit"
                    />
                </v-form>
            </v-card-text>
        </v-card>

        <div class="d-flex flex-row ga-2 pa-3 justify-start">
            <v-clipped-button
                color="secondary"
                prepend-icon="ti ti-arrow-left"
                :disabled="form.processing"
                @click="cancel"
            >
                {{ cancelLabel }}
            </v-clipped-button>
            <div class="flex-grow-1"></div>
            <slot
                name="actions"
                :form="form"
                :is-creating="isCreating"
                :can-save="canSave"
                :can-submit="canSubmit"
                :submit="submit"
            />
            <v-clipped-button
                v-if="canSubmit && !hideSaveAction"
                color="primary"
                prepend-icon="ti ti-device-floppy"
                :loading="form.processing"
                :disabled="!canSave"
                @click="submit"
            >
                {{ saveLabel }}
            </v-clipped-button>
        </div>
    </div>
</template>
