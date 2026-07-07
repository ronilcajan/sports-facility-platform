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
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid gap-12 md:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
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

                <div>
                    <h3 class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase">
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

                <div>
                    <h3 class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase">
                        Hours
                    </h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li
                            v-for="row in site.hours"
                            :key="row.day"
                            class="flex flex-col"
                        >
                            <span class="text-content-inverse/50">{{ row.day }}</span>
                            <span class="text-content-inverse/80">{{ row.value }}</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold tracking-widest text-content-inverse/40 uppercase">
                        Visit
                    </h3>
                    <address class="mt-4 space-y-2 text-sm text-content-inverse/70 not-italic">
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
                    <div class="mt-4 flex gap-4 text-sm">
                        <a
                            v-for="link in site.social"
                            :key="link.url"
                            :href="link.url"
                            target="_blank"
                            rel="noopener"
                            class="text-content-inverse/60 transition-colors hover:text-highlight"
                        >
                            {{ link.label }}
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="mt-14 flex flex-col gap-4 border-t border-content-inverse/10 pt-6 text-xs text-content-inverse/50 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ year }} {{ site.name }}. All rights reserved.</p>
                <div class="flex gap-6">
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
