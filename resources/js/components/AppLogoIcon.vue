<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';
import type { SiteData } from '@/types';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

const page = usePage();
const logo = computed(() => (page.props.site as SiteData | undefined)?.logo ?? '/logo.jpg');
</script>

<template>
    <img
        :src="logo"
        alt="Logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
