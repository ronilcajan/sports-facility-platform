<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SiteWordmark from '@/components/site/SiteWordmark.vue';
import { useSite } from '@/composables/useSite';
import { login, register } from '@/routes';
import { courts } from '@/routes/site';

const site = useSite();
const page = usePage();
const isAuthed = computed(() => Boolean(page.props.auth?.user));

const open = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-line bg-surface/90 text-content backdrop-blur-md transition-all duration-300"
    >
        <div
            class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6"
        >
            <!-- Logo Section -->
            <Link
                :href="'/'"
                class="shrink-0 transition-opacity hover:opacity-90"
                aria-label="Home"
            >
                <SiteWordmark />
            </Link>

            <!-- Desktop Navigation Section -->
            <nav class="hidden items-center gap-8 md:flex" aria-label="Primary">
                <Link
                    :href="'/'"
                    class="text-sm font-semibold text-content-muted transition-colors hover:text-brand"
                >
                    Home
                </Link>
                <Link
                    v-for="item in site.nav"
                    :key="item.href"
                    :href="item.href"
                    class="text-sm font-semibold text-content-muted transition-colors hover:text-brand"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Desktop Right Actions -->
            <div class="hidden items-center gap-6 md:flex">
                <template v-if="isAuthed">
                    <Link
                        :href="'/dashboard'"
                        class="text-sm font-semibold text-content transition-colors hover:text-brand"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-sm font-semibold text-content-muted transition-colors hover:text-brand"
                    >
                        Log In
                    </Link>
                    <span class="h-4 w-px bg-line"></span>
                    <Link
                        :href="register()"
                        class="text-sm font-semibold text-content-muted transition-colors hover:text-brand"
                    >
                        Register
                    </Link>
                </template>

                <Link
                    :href="courts()"
                    class="relative inline-flex items-center justify-center rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-brand/35"
                >
                    Book a Court Now
                </Link>
            </div>

            <!-- Mobile Hamburger Button -->
            <button
                type="button"
                class="inline-flex size-11 items-center justify-center rounded-full border border-line bg-surface-elevated/50 text-content transition-colors hover:bg-surface-elevated md:hidden"
                :aria-expanded="open"
                aria-label="Toggle menu"
                @click="open = !open"
            >
                <span class="sr-only">Menu</span>
                <svg
                    class="size-5 transition-transform duration-300"
                    :class="{ 'rotate-90': open }"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path v-if="!open" d="M4 7h16M4 12h16M4 17h16" />
                    <path v-else d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu Dropdown -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-4 opacity-0"
        >
            <div
                v-if="open"
                class="border-t border-line bg-surface/98 backdrop-blur-lg md:hidden"
            >
                <nav class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6">
                    <Link
                        :href="'/'"
                        class="rounded-xl px-4 py-3 text-base font-semibold text-content-muted transition-colors hover:bg-brand/10 hover:text-brand"
                        @click="open = false"
                    >
                        Home
                    </Link>
                    <Link
                        v-for="item in site.nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-xl px-4 py-3 text-base font-semibold text-content-muted transition-colors hover:bg-brand/10 hover:text-brand"
                        @click="open = false"
                    >
                        {{ item.label }}
                    </Link>

                    <div class="my-3 h-px bg-line"></div>

                    <div class="flex flex-col gap-3">
                        <template v-if="isAuthed">
                            <Link
                                :href="'/dashboard'"
                                class="rounded-xl px-4 py-3 text-base font-semibold text-content transition-colors hover:bg-brand/10 hover:text-brand"
                                @click="open = false"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <div class="grid grid-cols-2 gap-3 px-2">
                                <Link
                                    :href="login()"
                                    class="rounded-xl border border-line py-3 text-center text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated"
                                    @click="open = false"
                                >
                                    Log In
                                </Link>
                                <Link
                                    :href="register()"
                                    class="rounded-xl border border-line py-3 text-center text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated"
                                    @click="open = false"
                                >
                                    Register
                                </Link>
                            </div>
                        </template>
                        <Link
                            :href="courts()"
                            class="rounded-full bg-brand py-3.5 text-center text-base font-bold text-brand-foreground shadow-lg shadow-brand/15 transition-colors hover:bg-brand/90"
                            @click="open = false"
                        >
                            Book a Court Now
                        </Link>
                    </div>
                </nav>
            </div>
        </transition>
    </header>
</template>
