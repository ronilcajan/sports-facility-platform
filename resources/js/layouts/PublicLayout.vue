<script setup lang="ts">
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-chalk text-ink antialiased">
        <SiteHeader />
        <main class="flex-1">
            <slot />
        </main>
        <SiteFooter />
        <Toaster />
    </div>
</template>
