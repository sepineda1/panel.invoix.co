<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import Logo from '../Components/Logo.vue';

const page = usePage();

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen">
        <header class="sticky top-0 z-20 flex h-14 items-center border-b border-line bg-white px-4">
            <Link href="/empresas" class="flex items-center gap-2">
                <Logo :height="42" />
                <span class="text-[15px] font-bold text-ink">Panel INVOIX</span>
            </Link>
            <nav class="ml-8 hidden items-center gap-1 sm:flex">
                <Link href="/empresas" class="rounded-lg px-3 py-1.5 font-semibold text-ink hover:bg-primary-light hover:text-primary" :class="page.url.startsWith('/empresas') && 'text-primary'">Empresas</Link>
                <Link href="/actualizacion" class="rounded-lg px-3 py-1.5 font-semibold text-ink hover:bg-primary-light hover:text-primary" :class="page.url.startsWith('/actualizacion') && 'text-primary'">Actualización</Link>
                <Link href="/bitacora" class="rounded-lg px-3 py-1.5 font-semibold text-ink hover:bg-primary-light hover:text-primary" :class="page.url.startsWith('/bitacora') && 'text-primary'">Bitácora</Link>
            </nav>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-muted sm:inline">{{ page.props.auth.user?.name }}</span>
                <button type="button" class="s-btn s-btn-outline" @click="logout">Salir</button>
            </div>
        </header>
        <nav class="flex gap-2 border-b border-line bg-white px-4 py-2 sm:hidden">
            <Link href="/empresas" class="font-semibold text-primary">Empresas</Link>
            <Link href="/actualizacion" class="font-semibold text-primary">Actualización</Link>
            <Link href="/bitacora" class="font-semibold text-primary">Bitácora</Link>
        </nav>
        <main class="mx-auto w-full max-w-6xl px-4 py-6">
            <p v-if="page.props.flash?.success" class="mb-4 rounded-lg bg-[#e3f8f0] px-3 py-2 text-[#008a5c]">{{ page.props.flash.success }}</p>
            <p v-if="page.props.flash?.error" class="mb-4 rounded-lg bg-[#fdecec] px-3 py-2 text-[#c62828]">{{ page.props.flash.error }}</p>
            <slot />
        </main>
    </div>
</template>
