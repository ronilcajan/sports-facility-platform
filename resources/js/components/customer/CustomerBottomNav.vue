<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { LayoutGrid, CalendarDays, PlusCircle, User } from '@lucide/vue';

const page = usePage();
const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user as any);

// Only show floating bottom nav for Customer role (not for Super Admin / Admin / Staff who have their own dashboard interfaces)
const isCustomer = computed(() => {
    if (!user.value) return false;
    return !user.value.is_super_admin && !user.value.is_venue_admin && !user.value.is_staff;
});

const navItems = [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        isActive: (url: string) => url === '/dashboard' || url === '/',
    },
    {
        name: 'Bookings',
        href: '/my-bookings',
        icon: CalendarDays,
        isActive: (url: string) => url.startsWith('/my-bookings'),
    },
    {
        name: 'Explore',
        href: '/courts',
        icon: PlusCircle,
        isActive: (url: string) => url.startsWith('/courts') || url.startsWith('/venues'),
    },
    {
        name: 'Profile',
        href: '/settings/profile',
        icon: User,
        isActive: (url: string) => url.startsWith('/settings'),
    },
];
</script>

<template>
    <div
        v-if="isCustomer"
        class="md:hidden fixed bottom-4 left-4 right-4 z-50 max-w-md mx-auto bg-neutral-900/90 dark:bg-neutral-950/95 backdrop-blur-xl border border-neutral-800/80 shadow-2xl rounded-full px-3 py-2 flex items-center justify-around transition-all duration-300"
    >
        <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            class="relative flex flex-col items-center justify-center py-1.5 px-3 rounded-full transition-all duration-200 group"
            :class="[
                item.isActive(currentUrl)
                    ? 'text-emerald-400 font-bold bg-emerald-500/10 scale-105'
                    : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-800/50',
            ]"
        >
            <component
                :is="item.icon"
                class="w-5 h-5 transition-transform duration-200 group-hover:scale-110"
                :class="[item.isActive(currentUrl) ? 'text-emerald-400' : 'text-neutral-400']"
            />
            <span class="text-[10px] tracking-tight mt-0.5 font-semibold">
                {{ item.name }}
            </span>

            <span
                v-if="item.isActive(currentUrl)"
                class="absolute -bottom-1 w-1.5 h-1.5 bg-emerald-400 rounded-full shadow-sm shadow-emerald-400/50"
            ></span>
        </Link>
    </div>
</template>
