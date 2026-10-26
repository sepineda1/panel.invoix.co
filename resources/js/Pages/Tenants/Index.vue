<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import PanelLayout from '../../Layouts/PanelLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

defineProps({
    tenants: { type: Array, required: true },
});

const page = usePage();
</script>

<template>
    <Head title="Empresas" />
    <PanelLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-bold text-ink">Empresas</h1>
            <Link href="/empresas/nueva" class="s-btn s-btn-primary font-bold">Nueva empresa</Link>
        </div>
        <div class="s-card overflow-x-auto">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Subdominio</th>
                        <th>Estado</th>
                        <th>Imagen</th>
                        <th>Creada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!tenants.length">
                        <td colspan="6" class="py-8 text-center text-muted">Todavía no hay empresas.</td>
                    </tr>
                    <tr v-for="tenant in tenants" :key="tenant.id">
                        <td class="font-semibold text-ink">{{ tenant.name }}</td>
                        <td><a :href="tenant.app_url" class="text-primary" target="_blank" rel="noreferrer">{{ tenant.slug }}.{{ page.props.domain }}</a></td>
                        <td><StatusBadge :status="tenant.status" :label="tenant.status_label" /></td>
                        <td class="text-muted">{{ tenant.image_tag || '—' }}</td>
                        <td class="text-muted">{{ tenant.created_at }}</td>
                        <td class="text-right"><Link :href="`/empresas/${tenant.id}`" class="s-btn s-btn-outline">Ver</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>
