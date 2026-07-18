<script setup lang="ts">
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import MobileBottomNav from '@/components/site/MobileBottomNav.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-surface text-content antialiased">
        <SiteHeader />
        <main class="flex-1 pb-20 md:pb-0">
            <slot />
        </main>
        <SiteFooter />
        <MobileBottomNav />
        <Toaster />
    </div>
</template>

