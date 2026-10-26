<script setup>
defineProps({
    status: { type: String, required: true },
    label: { type: String, required: true },
});

const colors = {
    provisioning: 'background:#fff4de;color:#a86b00',
    running: 'background:#e3f8f0;color:#008a5c',
    updating: 'background:#e0f2fe;color:#075985',
    failed: 'background:#fdecec;color:#c62828',
    stopped: 'background:#f1f3f5;color:#64748b',
    deleting: 'background:#fdecec;color:#c62828',
};
</script>

<template>
    <span class="s-badge" :style="colors[status] || colors.stopped">{{ label }}</span>
</template>
