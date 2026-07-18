<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Home, LayoutGrid, Images, Info, CalendarDays, LogIn, LayoutDashboard } from '@lucide/vue';

const page = usePage();
const isAuthed = computed(() => Boolean(page.props.auth?.user));
const currentPath = computed(() => page.url?.split('?')[0] ?? '/');

function isActive(href: string): boolean {
    if (href === '/') return currentPath.value === '/';
    return currentPath.value.startsWith(href);
}

const publicNav = [
    { href: '/', label: 'Home', icon: Home },
    { href: '/courts', label: 'Courts', icon: LayoutGrid },
    { href: '/gallery', label: 'Gallery', icon: Images },
    { href: '/about', label: 'About', icon: Info },
];
</script>

<template>
    <nav
        class="fixed bottom-0 inset-x-0 z-50 border-t border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-950/95 backdrop-blur-xl md:hidden safe-bottom"
        aria-label="Mobile navigation"
    >
        <div class="flex items-center justify-around px-1 h-16">
            <Link
                v-for="item in publicNav"
                :key="item.href"
                :href="item.href"
                class="flex flex-col items-center justify-center gap-0.5 min-w-[3.5rem] py-1.5 rounded-xl transition-colors duration-200"
                :class="isActive(item.href)
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'"
            >
                <component
                    :is="item.icon"
                    class="w-5 h-5 transition-transform duration-200"
                    :class="{ 'scale-110': isActive(item.href) }"
                />
                <span
                    class="text-[10px] font-semibold leading-tight"
                    :class="{ 'font-extrabold': isActive(item.href) }"
                >
                    {{ item.label }}
                </span>
                <span
                    v-if="isActive(item.href)"
                    class="absolute bottom-1 w-1 h-1 rounded-full bg-emerald-500"
                ></span>
            </Link>

            <!-- Authenticated: Bookings tab -->
            <Link
                v-if="isAuthed"
                href="/my-bookings"
                class="flex flex-col items-center justify-center gap-0.5 min-w-[3.5rem] py-1.5 rounded-xl transition-colors duration-200"
                :class="isActive('/my-bookings')
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'"
            >
                <CalendarDays
                    class="w-5 h-5 transition-transform duration-200"
                    :class="{ 'scale-110': isActive('/my-bookings') }"
                />
                <span
                    class="text-[10px] font-semibold leading-tight"
                    :class="{ 'font-extrabold': isActive('/my-bookings') }"
                >
                    Bookings
                </span>
            </Link>

            <!-- Not authenticated: Login tab -->
            <Link
                v-if="!isAuthed"
                href="/login"
                class="flex flex-col items-center justify-center gap-0.5 min-w-[3.5rem] py-1.5 rounded-xl transition-colors duration-200"
                :class="isActive('/login')
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'"
            >
                <LogIn class="w-5 h-5" />
                <span class="text-[10px] font-semibold leading-tight">Login</span>
            </Link>

            <!-- Authenticated: Dashboard tab -->
            <Link
                v-if="isAuthed"
                href="/dashboard"
                class="flex flex-col items-center justify-center gap-0.5 min-w-[3.5rem] py-1.5 rounded-xl transition-colors duration-200"
                :class="isActive('/dashboard')
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300'"
            >
                <LayoutDashboard
                    class="w-5 h-5 transition-transform duration-200"
                    :class="{ 'scale-110': isActive('/dashboard') }"
                />
                <span
                    class="text-[10px] font-semibold leading-tight"
                    :class="{ 'font-extrabold': isActive('/dashboard') }"
                >
                    Dashboard
                </span>
            </Link>
        </div>
    </nav>
</template>

<style scoped>
.safe-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
