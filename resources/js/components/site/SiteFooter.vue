<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SiteWordmark from '@/components/site/SiteWordmark.vue';
import { useSite } from '@/composables/useSite';
import { courts } from '@/routes/site';

const site = useSite();
const year = 2026;
</script>

<template>
    <footer class="bg-surface-inverse text-content-inverse">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <!-- Responsive grid: 1-col → 2-col (sm) → 4-col (md) -->
            <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-[1.4fr_1fr_1fr_1.2fr] md:gap-12">
                <!-- Brand + CTA -->
                <div>
                    <SiteWordmark tone="chalk" />
                    <p class="mt-4 max-w-xs text-sm text-content-inverse/60">
                        {{ site.tagline }}
                    </p>
                    <Link
                        :href="courts()"
                        class="mt-6 inline-flex rounded-full bg-brand px-5 py-2 text-sm font-semibold text-brand-foreground transition-transform hover:-translate-y-0.5"
                    >
                        Book a court
                    </Link>
                </div>

                <!-- Explore nav -->
                <div>
                    <h3
                        class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase"
                    >
                        Explore
                    </h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li v-for="item in site.nav" :key="item.href">
                            <Link
                                :href="item.href"
                                class="text-content-inverse/70 transition-colors hover:text-highlight"
                            >
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Hours -->
                <div>
                    <h3
                        class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase"
                    >
                        Hours
                    </h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li
                            v-for="row in site.hours"
                            :key="row.day"
                            class="flex flex-col"
                        >
                            <span class="text-content-inverse/50">{{
                                row.day
                            }}</span>
                            <span class="text-content-inverse/80">{{
                                row.value
                            }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3
                        class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase"
                    >
                        Visit
                    </h3>
                    <address
                        class="mt-4 space-y-2 text-sm text-content-inverse/70 not-italic"
                    >
                        <p>{{ site.contact.address_line }}</p>
                        <p>
                            <a
                                :href="`tel:${site.contact.phone}`"
                                class="hover:text-highlight"
                            >
                                {{ site.contact.phone }}
                            </a>
                        </p>
                        <p>
                            <a
                                :href="`mailto:${site.contact.email}`"
                                class="hover:text-highlight"
                            >
                                {{ site.contact.email }}
                            </a>
                        </p>
                    </address>
                    <div class="mt-4 flex gap-3">
                        <a
                            v-for="link in site.social"
                            :key="link.url"
                            :href="link.url"
                            target="_blank"
                            rel="noopener"
                            :aria-label="link.label"
                            class="flex size-9 items-center justify-center rounded-full bg-content-inverse/10 text-content-inverse/70 transition-colors hover:bg-highlight hover:text-surface-inverse"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom legal bar -->
            <div
                class="mt-12 flex flex-col gap-4 border-t border-content-inverse/10 pt-6 text-xs text-content-inverse/50 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ year }} {{ site.name }}. All rights reserved.</p>
                <div class="flex flex-wrap gap-4 sm:gap-6">
                    <Link
                        v-for="item in site.legal"
                        :key="item.href"
                        :href="item.href"
                        class="transition-colors hover:text-content-inverse"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </div>
        </div>
    </footer>
</template>

