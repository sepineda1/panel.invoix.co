<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PanelLayout from '../../Layouts/PanelLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    adminPassword: { type: String, default: null },
    operations: { type: Array, required: true },
});

const removeForm = useForm({ confirm: '' });
let timer;

function busy() {
    return ['provisioning', 'updating', 'deleting'].includes(props.tenant.status)
        || props.operations.some((item) => item.status === 'running');
}

function reload() {
    router.reload({ only: ['tenant', 'operations', 'adminPassword'] });
}

function arm() {
    clearInterval(timer);
    if (busy()) {
        timer = setInterval(reload, 3000);
    }
}

function post(path) {
    router.post(path);
}

function remove() {
    removeForm.post(`/empresas/${props.tenant.id}/eliminar`);
}

function dismissPassword() {
    router.post(`/empresas/${props.tenant.id}/contrasena-vista`);
}

onMounted(arm);
watch(() => props.tenant.status, arm);
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <Head :title="tenant.name" />
    <PanelLayout>
        <Link href="/empresas" class="text-primary">← Empresas</Link>
        <div class="mt-2 mb-4 flex flex-wrap items-center gap-3">
            <h1 class="text-lg font-bold text-ink">{{ tenant.name }}</h1>
            <StatusBadge :status="tenant.status" :label="tenant.status_label" />
        </div>

        <div v-if="adminPassword" class="s-card mb-4 border-warn bg-[#fff8eb] p-4">
            <p class="font-bold text-ink">Contraseña del administrador</p>
            <p class="mt-1 text-muted">Se muestra una sola vez. Al salir de esta pantalla deja de verse.</p>
            <p class="mt-2 font-mono text-[15px] text-ink">{{ adminPassword }}</p>
            <button type="button" class="s-btn s-btn-outline mt-3" @click="dismissPassword">Ya la guardé</button>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <a :href="tenant.app_url" class="s-btn s-btn-primary" target="_blank" rel="noreferrer">Abrir sitio</a>
            <button v-if="tenant.status === 'running'" type="button" class="s-btn s-btn-outline" @click="post(`/empresas/${tenant.id}/reiniciar`)">Reiniciar</button>
            <button v-if="tenant.status === 'running'" type="button" class="s-btn s-btn-outline" @click="post(`/empresas/${tenant.id}/desactivar`)">Desactivar</button>
            <button v-if="tenant.status === 'stopped'" type="button" class="s-btn s-btn-outline" @click="post(`/empresas/${tenant.id}/activar`)">Activar</button>
            <button v-if="tenant.status === 'failed'" type="button" class="s-btn s-btn-outline" @click="post(`/empresas/${tenant.id}/reintentar`)">Reintentar</button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="s-card p-4">
                <h2 class="mb-3 font-bold text-ink">Datos</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Subdominio</dt><dd><a :href="tenant.app_url" class="text-primary" target="_blank" rel="noreferrer">{{ tenant.app_url }}</a></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Administrador</dt><dd>{{ tenant.admin_name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Correo</dt><dd>{{ tenant.admin_email }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Base</dt><dd>{{ tenant.database_name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Prefijo Redis</dt><dd>{{ tenant.redis_prefix }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Imagen</dt><dd class="text-right">{{ tenant.image_ref || '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Creada</dt><dd>{{ tenant.created_at }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Imagen actualizada</dt><dd>{{ tenant.image_updated_at || '—' }}</dd></div>
                </dl>
            </section>
            <section class="s-card p-4">
                <h2 class="mb-3 font-bold text-ink">Último resultado</h2>
                <p v-if="tenant.last_error" class="text-danger">{{ tenant.last_error }}</p>
                <p v-else class="text-muted">Sin errores registrados.</p>
                <p v-if="tenant.checkpoint" class="mt-2 text-muted">Último paso completado: {{ tenant.checkpoint }}</p>
            </section>
        </div>

        <section class="s-card mt-4 overflow-x-auto">
            <h2 class="px-4 pt-4 font-bold text-ink">Aprovisionamiento</h2>
            <table class="s-table">
                <thead>
                    <tr><th>Fecha</th><th>Operación</th><th>Estado</th><th>Operador</th><th>Salida</th></tr>
                </thead>
                <tbody>
                    <tr v-if="!operations.length"><td colspan="5" class="text-muted">Sin registros.</td></tr>
                    <tr v-for="item in operations" :key="item.id">
                        <td class="whitespace-nowrap text-muted">{{ item.created_at }}</td>
                        <td>{{ item.type_label }}</td>
                        <td>{{ item.status_label }}</td>
                        <td>{{ item.operator }}</td>
                        <td class="max-w-md whitespace-pre-wrap text-muted">{{ item.output }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <form class="s-card mt-4 max-w-xl p-4" @submit.prevent="remove">
            <h2 class="font-bold text-danger">Eliminar</h2>
            <p class="mt-1 text-muted">Escribe <strong>{{ tenant.slug }}</strong> para borrar contenedores, archivos y la base. Esta acción queda en la bitácora.</p>
            <input v-model="removeForm.confirm" class="s-input mt-3" :placeholder="tenant.slug" :class="{ 'is-invalid': removeForm.errors.confirm }">
            <p v-if="removeForm.errors.confirm" class="s-error">{{ removeForm.errors.confirm }}</p>
            <button type="submit" class="s-btn s-btn-danger mt-3" :disabled="removeForm.processing || tenant.status === 'deleting'">Eliminar</button>
        </form>
    </PanelLayout>
</template>
