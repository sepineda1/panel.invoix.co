<script setup>
const id = `invoix-${Math.random().toString(36).slice(2, 8)}`;
defineProps({
    height: { type: [Number, String], default: 42 },
});
</script>

<template>
    <svg :height="height" viewBox="0 0 64 64" fill="none" role="img" aria-label="INVOIX">
        <defs>
            <linearGradient :id="id" x1="8" y1="58" x2="56" y2="6" gradientUnits="userSpaceOnUse">
                <stop stop-color="#7dd3fc" />
                <stop offset="0.45" stop-color="#38bdf8" />
                <stop offset="1" stop-color="#0275b3" />
            </linearGradient>
        </defs>
        <rect x="6" y="36" width="14" height="22" rx="3" :fill="`url(#${id})`" />
        <rect x="25" y="22" width="14" height="36" rx="3" :fill="`url(#${id})`" />
        <rect x="44" y="8" width="14" height="50" rx="3" :fill="`url(#${id})`" />
    </svg>
</template>
