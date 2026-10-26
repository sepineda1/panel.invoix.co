<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PanelLayout from '../../Layouts/PanelLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

const props = defineProps({
    image: { type: String, required: true },
    imageValid: { type: Boolean, required: true },
    using: { type: Number, required: true },
    eligible: { type: Number, required: true },
    progress: { type: Object, default: null },
    tenants: { type: Array, required: true },
});

const form = useForm({});
let timer;

function arm() {
    clearInterval(timer);
    if (props.progress?.active) {
        timer = setInterval(() => router.reload({ only: ['progress', 'tenants', 'using', 'eligible', 'image'] }), 3000);
    }
}

function updateAll() {
    form.post('/actualizacion');
}

onMounted(arm);
watch(() => props.progress?.active, arm);
onUnmounted(() => clearInterval(timer));
</script>

<template>
    <Head title="Actualización" />
    <PanelLayout>
        <h1 class="mb-4 text-lg font-bold text-ink">Actualización</h1>
        <section class="s-card mb-4 p-4">
            <p class="text-muted">Imagen publicada</p>
            <p class="mt-1 font-mono text-ink">{{ image }}</p>
            <p class="mt-3 text-ink"><strong>{{ using }}</strong> empresas en línea la tienen. <strong>{{ eligible }}</strong> se pueden actualizar.</p>
            <button type="button" class="s-btn s-btn-primary mt-4 font-bold" :disabled="!imageValid || progress?.active || form.processing" @click="updateAll">Actualizar todas</button>
        </section>

        <section v-if="progress" class="s-card mb-4 p-4">
            <h2 class="font-bold text-ink">{{ progress.active ? 'Actualización en curso' : 'Última actualización' }}</h2>
            <p class="mt-2 text-muted">{{ progress.processed }} de {{ progress.total }}<span v-if="progress.current"> · ahora {{ progress.current }}</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded bg-th">
                <div class="h-full bg-primary" :style="{ width: progress.total ? `${Math.round((progress.processed / progress.total) * 100)}%` : '0%' }"></div>
            </div>
            <ul v-if="progress.failures?.length" class="mt-3 space-y-1 text-danger">
                <li v-for="failure in progress.failures" :key="failure.slug">{{ failure.slug }}: {{ failure.error }}</li>
            </ul>
        </section>

        <div class="s-card overflow-x-auto">
            <table class="s-table">
                <thead>
                    <tr><th>Empresa</th><th>Estado</th><th>Imagen</th></tr>
                </thead>
                <tbody>
                    <tr v-for="tenant in tenants" :key="tenant.id">
                        <td>{{ tenant.name }}</td>
                        <td><StatusBadge :status="tenant.status" :label="tenant.status_label" /></td>
                        <td class="text-muted">{{ tenant.image_tag || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>
