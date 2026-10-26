<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Logo from '../../Components/Logo.vue';

const form = useForm({ email: '', password: '', remember: true });
const showPassword = ref(false);

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Inicio de sesión" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-surface px-4">
        <Logo :height="72" class="mb-6" />
        <form class="s-card w-[390px] max-w-full px-[30px] py-7 shadow-[0_2px_10px_rgba(0,0,0,.08)]" @submit.prevent="submit">
            <h1 class="text-[22px] font-bold text-ink">Inicio de sesión</h1>
            <p class="mb-5 mt-1 text-ink-soft">Operadores del panel. No hay registro público.</p>

            <input v-model="form.email" type="email" autocomplete="username" placeholder="Correo electrónico*" class="s-input" :class="{ 'is-invalid': form.errors.email }" autofocus>
            <p v-if="form.errors.email" class="s-error">{{ form.errors.email }}</p>

            <div class="relative mt-3">
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="Contraseña*" class="s-input pr-16" :class="{ 'is-invalid': form.errors.password }">
                <button type="button" class="absolute right-3 top-2 text-xs font-semibold text-primary" @click="showPassword = !showPassword">{{ showPassword ? 'Ocultar' : 'Ver' }}</button>
            </div>
            <p v-if="form.errors.password" class="s-error">{{ form.errors.password }}</p>

            <label class="mt-3 flex items-center gap-2 text-muted">
                <input v-model="form.remember" type="checkbox">
                Recordarme
            </label>

            <button type="submit" class="s-btn s-btn-primary mt-4 w-full font-bold" :disabled="form.processing">Continuar</button>
        </form>
    </div>
</template>
