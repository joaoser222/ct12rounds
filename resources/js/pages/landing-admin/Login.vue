<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import logo from '@/assets/logo.webp';

defineOptions({ layout: null });

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/landing-admin/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <v-main
        theme="dark"
        class="d-flex align-center justify-center"
        style="min-height: 100vh"
    >
        <v-container
            fluid
            class="fill-height d-flex align-center justify-center pa-4"
        >
            <v-card width="420" color="secondary">
                <v-card-text class="pa-8">
                    <div class="d-flex justify-center mb-6">
                        <v-img :src="logo" height="48" />
                    </div>

                    <h1 class="text-h6 font-weight-medium text-center mb-1">
                        Painel da Landing Page
                    </h1>
                    <p
                        class="text-body-2 text-medium-emphasis text-center mb-6"
                    >
                        Acesso restrito para edição do site
                    </p>

                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            autocomplete="email"
                            :error-messages="form.errors.email"
                            class="mb-3"
                        />
                        <password-field
                            v-model="form.password"
                            label="Senha"
                            autocomplete="current-password"
                            :error-messages="form.errors.password"
                            class="mb-4"
                        />

                        <v-btn
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            Entrar
                        </v-btn>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>
