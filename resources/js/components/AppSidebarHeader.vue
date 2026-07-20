<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { dashboard, logout } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-4 md:px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
    >
        <!-- Desktop Header View: Sidebar Toggle & Breadcrumbs -->
        <div class="hidden md:flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Mobile Header View: App Logo (Pic 2) on left & Logout button on right -->
        <div class="md:hidden flex items-center justify-between w-full">
            <Link :href="dashboard()" class="flex items-center gap-1.5 focus:outline-none">
                <AppLogo />
            </Link>

            <Link
                :href="logout()"
                method="post"
                as="button"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-500 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 px-3 py-1.5 rounded-full transition-colors"
            >
                <LogOut class="w-3.5 h-3.5" />
                <span>Logout</span>
            </Link>
        </div>
    </header>
</template>
