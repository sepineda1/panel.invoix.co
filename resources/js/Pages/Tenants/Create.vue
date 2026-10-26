<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PanelLayout from '../../Layouts/PanelLayout.vue';
import { suggestSlug } from '../../slug';

const page = usePage();
const form = useForm({
    name: '',
    slug: '',
    admin_name: '',
    admin_email: '',
});
const slugTouched = ref(false);

watch(() => form.name, (name) => {
    if (!slugTouched.value) {
        form.slug = suggestSlug(name);
    }
});

function submit() {
    form.post('/empresas');
}
</script>

<template>
    <Head title="Nueva empresa" />
    <PanelLayout>
        <div class="mb-4">
            <Link href="/empresas" class="text-primary">← Empresas</Link>
            <h1 class="mt-2 text-lg font-bold text-ink">Nueva empresa</h1>
        </div>
        <form class="s-card max-w-xl space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label class="s-label" for="name">Nombre comercial</label>
                <input id="name" v-model="form.name" class="s-input" :class="{ 'is-invalid': form.errors.name }" placeholder="Factura Mía">
                <p v-if="form.errors.name" class="s-error">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="s-label" for="slug">Subdominio</label>
                <div class="flex items-center gap-2">
                    <input id="slug" v-model="form.slug" class="s-input" :class="{ 'is-invalid': form.errors.slug }" placeholder="facturamia" @input="slugTouched = true">
                    <span class="text-muted">.{{ page.props.domain }}</span>
                </div>
                <p class="mt-1 text-muted">Sugerido a partir del nombre, sin tildes. Puedes editarlo.</p>
                <p v-if="form.errors.slug" class="s-error">{{ form.errors.slug }}</p>
            </div>
            <div>
                <label class="s-label" for="admin_name">Nombre del administrador</label>
                <input id="admin_name" v-model="form.admin_name" class="s-input" :class="{ 'is-invalid': form.errors.admin_name }">
                <p v-if="form.errors.admin_name" class="s-error">{{ form.errors.admin_name }}</p>
            </div>
            <div>
                <label class="s-label" for="admin_email">Correo del administrador</label>
                <input id="admin_email" v-model="form.admin_email" type="email" class="s-input" :class="{ 'is-invalid': form.errors.admin_email }">
                <p v-if="form.errors.admin_email" class="s-error">{{ form.errors.admin_email }}</p>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="s-btn s-btn-primary font-bold" :disabled="form.processing">Crear empresa</button>
                <Link href="/empresas" class="s-btn s-btn-outline">Cancelar</Link>
            </div>
        </form>
    </PanelLayout>
</template>
