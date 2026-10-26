<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PanelLayout from '../../Layouts/PanelLayout.vue';

defineProps({
    operations: { type: Array, required: true },
});
</script>

<template>
    <Head title="Bitácora" />
    <PanelLayout>
        <h1 class="mb-4 text-lg font-bold text-ink">Bitácora</h1>
        <div class="s-card overflow-x-auto">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Operador</th>
                        <th>Empresa</th>
                        <th>Operación</th>
                        <th>Estado</th>
                        <th>Salida</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!operations.length">
                        <td colspan="6" class="py-8 text-center text-muted">No hay operaciones.</td>
                    </tr>
                    <tr v-for="item in operations" :key="item.id">
                        <td class="whitespace-nowrap text-muted">{{ item.created_at }}</td>
                        <td>{{ item.operator }}</td>
                        <td>
                            <Link v-if="item.tenant_id" :href="`/empresas/${item.tenant_id}`" class="text-primary">{{ item.company }}</Link>
                            <span v-else>{{ item.company }}</span>
                            <div class="text-muted">{{ item.slug }}</div>
                        </td>
                        <td>{{ item.type_label }}</td>
                        <td>{{ item.status_label }}</td>
                        <td class="max-w-xs truncate text-muted" :title="item.output">{{ item.output }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PanelLayout>
</template>
