<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SiteWordmark from '@/components/site/SiteWordmark.vue';
import { useSite } from '@/composables/useSite';
import { login } from '@/routes';
import { courts } from '@/routes/site';

const site = useSite();
const page = usePage();
const isAuthed = computed(() => Boolean(page.props.auth?.user));

const open = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-line bg-surface/80 text-content backdrop-blur supports-[backdrop-filter]:bg-surface/70"
    >
        <div
            class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6"
        >
            <Link :href="'/'" class="shrink-0" aria-label="Home">
                <SiteWordmark />
            </Link>

            <nav class="hidden items-center gap-7 md:flex" aria-label="Primary">
                <Link
                    v-for="item in site.nav"
                    :key="item.href"
                    :href="item.href"
                    class="text-sm font-medium text-content-muted transition-colors hover:text-content"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                <Link
                    :href="isAuthed ? '/dashboard' : login()"
                    class="text-sm font-medium text-content-muted transition-colors hover:text-content"
                >
                    {{ isAuthed ? 'Dashboard' : 'Log in' }}
                </Link>
                <Link
                    :href="courts()"
                    class="rounded-full bg-brand px-5 py-2 text-sm font-semibold text-brand-foreground transition-transform hover:-translate-y-0.5"
                >
                    Book a court
                </Link>
            </div>

            <button
                type="button"
                class="inline-flex size-10 items-center justify-center rounded-md text-content md:hidden"
                :aria-expanded="open"
                aria-label="Toggle menu"
                @click="open = !open"
            >
                <span class="sr-only">Menu</span>
                <svg
                    class="size-6"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path v-if="!open" d="M4 7h16M4 12h16M4 17h16" />
                    <path v-else d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <div v-if="open" class="border-t border-line bg-surface md:hidden">
            <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-4">
                <Link
                    v-for="item in site.nav"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-md px-3 py-2 text-sm font-medium text-content-muted hover:bg-content/5 hover:text-content"
                    @click="open = false"
                >
                    {{ item.label }}
                </Link>
                <div class="mt-3 flex flex-col gap-2">
                    <Link
                        :href="isAuthed ? '/dashboard' : login()"
                        class="rounded-md px-3 py-2 text-sm font-medium text-content-muted hover:bg-content/5 hover:text-content"
                    >
                        {{ isAuthed ? 'Dashboard' : 'Log in' }}
                    </Link>
                    <Link
                        :href="courts()"
                        class="rounded-full bg-brand px-5 py-2 text-center text-sm font-semibold text-brand-foreground"
                    >
                        Book a court
                    </Link>
                </div>
            </nav>
        </div>
    </header>
</template>
