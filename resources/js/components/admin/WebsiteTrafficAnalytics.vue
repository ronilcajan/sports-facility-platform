<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    Activity,
    Eye,
    Users,
    Clock,
    TrendingUp,
    Globe,
    Smartphone,
    Monitor,
    Tablet,
    Compass,
    ArrowUpRight,
    Zap,
    BarChart3,
    Sparkles,
    LayoutGrid,
} from '@lucide/vue';

export interface TrafficSummary {
    totalPageViews: number;
    uniqueVisitors: number;
    avgSessionTime: string;
    bounceRate: string;
    viewsGrowth: string;
    visitorsGrowth: string;
}

export interface TrafficTrendPoint {
    date: string;
    label: string;
    views: number;
    visitors: number;
}

export interface TopPageItem {
    name: string;
    url: string;
    category: string;
    views: number;
    visitors: number;
    conversion: string;
}

export interface DeviceBreakdownItem {
    device: string;
    percentage: number;
    count: number;
    color: string;
}

export interface SourceBreakdownItem {
    source: string;
    percentage: number;
    color: string;
}

export interface TrafficAnalyticsData {
    summary: TrafficSummary;
    trend: TrafficTrendPoint[];
    topPages: TopPageItem[];
    deviceBreakdown: DeviceBreakdownItem[];
    sourcesBreakdown: SourceBreakdownItem[];
}

const props = defineProps<{
    analytics: TrafficAnalyticsData;
}>();

const timeRange = ref<'7d' | '30d' | '90d'>('30d');
const activeAnalyticsTab = ref<'all' | 'trend' | 'pages' | 'devices'>('all');

// Filter trend based on timeframe selector
const filteredTrend = computed(() => {
    if (timeRange.value === '7d') {
        return props.analytics.trend.slice(-7);
    }
    return props.analytics.trend;
});

// Calculate max views for SVG chart scaling
const maxViews = computed(() => {
    const max = Math.max(...filteredTrend.value.map((t) => t.views), 100);
    return Math.ceil(max / 100) * 100;
});

// SVG Chart Path Generation
const chartWidth = 700;
const chartHeight = 220;

const pointsViews = computed(() => {
    const data = filteredTrend.value;
    if (data.length === 0) return '';
    return data
        .map((d, i) => {
            const x = (i / (data.length - 1)) * chartWidth;
            const y = chartHeight - (d.views / maxViews.value) * (chartHeight - 30) - 15;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
});

const pointsVisitors = computed(() => {
    const data = filteredTrend.value;
    if (data.length === 0) return '';
    return data
        .map((d, i) => {
            const x = (i / (data.length - 1)) * chartWidth;
            const y = chartHeight - (d.visitors / maxViews.value) * (chartHeight - 30) - 15;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
});

const areaPathViews = computed(() => {
    if (!pointsViews.value) return '';
    const firstX = 0;
    const lastX = chartWidth;
    return `M ${firstX},${chartHeight} L ${pointsViews.value.replace(/ /g, ' L ')} L ${lastX},${chartHeight} Z`;
});

function formatNumber(num: number): string {
    return num.toLocaleString('en-US');
}
</script>

<template>
    <div class="space-y-6">
        <!-- ── Header Banner ─────────────────────────────────── -->
        <div class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-900 via-teal-900 to-neutral-900 p-6 text-white shadow-xl sm:p-8">
            <!-- Background Glow Blobs -->
            <div class="pointer-events-none absolute -top-12 -right-12 size-64 rounded-full bg-emerald-500/10 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-16 -left-16 size-64 rounded-full bg-teal-500/10 blur-3xl" />

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-500/15 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-300 backdrop-blur-md">
                        <Sparkles class="size-3.5" />
                        <span>Super Admin Telemetry</span>
                    </div>
                    <h2 class="mt-3 text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                        <Activity class="size-7 text-emerald-400" />
                        Website Traffic &amp; Visitor Analytics
                    </h2>
                    <p class="mt-1 text-sm text-emerald-100/80 max-w-2xl">
                        Real-time visitor traffic monitoring, venue page engagements, device breakdown, and customer acquisition channels across the platform.
                    </p>
                </div>

                <!-- Time Period Selector Buttons -->
                <div class="flex items-center gap-1.5 rounded-2xl bg-white/10 p-1.5 backdrop-blur-md shrink-0 border border-white/10">
                    <button
                        type="button"
                        @click="timeRange = '7d'"
                        class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all cursor-pointer"
                        :class="timeRange === '7d' ? 'bg-emerald-500 text-white shadow-md' : 'text-emerald-100 hover:bg-white/10'"
                    >
                        Last 7 Days
                    </button>
                    <button
                        type="button"
                        @click="timeRange = '30d'"
                        class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition-all cursor-pointer"
                        :class="timeRange === '30d' ? 'bg-emerald-500 text-white shadow-md' : 'text-emerald-100 hover:bg-white/10'"
                    >
                        Last 30 Days
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Sub-tab Navigation Bar ──────────────────────────── -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 dark:border-neutral-800 pb-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'all'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeAnalyticsTab === 'all' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700'"
                >
                    <LayoutGrid class="size-3.5" />
                    All Analytics Overview
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'trend'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeAnalyticsTab === 'trend' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700'"
                >
                    <BarChart3 class="size-3.5" />
                    Traffic Trend &amp; Visitors
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'pages'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeAnalyticsTab === 'pages' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700'"
                >
                    <Globe class="size-3.5" />
                    Popular Pages &amp; Conversions
                </button>
                <button
                    type="button"
                    @click="activeAnalyticsTab = 'devices'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="activeAnalyticsTab === 'devices' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700'"
                >
                    <Smartphone class="size-3.5" />
                    Devices &amp; Acquisition
                </button>
            </div>
        </div>

        <!-- ── Summary KPI Cards (Visible in 'all' and 'trend') ── -->
        <div v-if="activeAnalyticsTab === 'all' || activeAnalyticsTab === 'trend'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Page Views Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Total Page Views</span>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <Eye class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-neutral-900 dark:text-white tabular-nums">
                        {{ formatNumber(analytics.summary.totalPageViews) }}
                    </span>
                    <span class="inline-flex items-center gap-0.5 text-xs font-black text-emerald-600 dark:text-emerald-400">
                        <TrendingUp class="size-3.5" />
                        {{ analytics.summary.viewsGrowth }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">Across all catalog &amp; court pages</p>
            </div>

            <!-- Unique Visitors Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Unique Visitors</span>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">
                        <Users class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-neutral-900 dark:text-white tabular-nums">
                        {{ formatNumber(analytics.summary.uniqueVisitors) }}
                    </span>
                    <span class="inline-flex items-center gap-0.5 text-xs font-black text-teal-600 dark:text-teal-400">
                        <TrendingUp class="size-3.5" />
                        {{ analytics.summary.visitorsGrowth }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">Unique IP &amp; session users</p>
            </div>

            <!-- Avg Session Duration Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Avg. Session Time</span>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400">
                        <Clock class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-neutral-900 dark:text-white tabular-nums">
                        {{ analytics.summary.avgSessionTime }}
                    </span>
                    <span class="inline-flex items-center gap-0.5 text-xs font-bold text-violet-600 dark:text-violet-400">
                        +8.2% avg
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">Time spent exploring courts</p>
            </div>

            <!-- Bounce Rate Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-neutral-400">Bounce Rate</span>
                    <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                        <Compass class="size-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span class="text-2xl font-black text-neutral-900 dark:text-white tabular-nums">
                        {{ analytics.summary.bounceRate }}
                    </span>
                    <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        -3.4% lower
                    </span>
                </div>
                <p class="mt-1 text-[11px] font-semibold text-neutral-400">Single-page exits ratio</p>
            </div>
        </div>

        <!-- ── Interactive Traffic Trend Chart (Visible in 'all' and 'trend') ── -->
        <div v-if="activeAnalyticsTab === 'all' || activeAnalyticsTab === 'trend'" class="rounded-3xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-neutral-100 dark:border-neutral-800">
                <div>
                    <h3 class="text-base font-black text-neutral-900 dark:text-white flex items-center gap-2">
                        <BarChart3 class="size-5 text-emerald-500" />
                        Daily Page Views &amp; Visitor Trend
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Comparing total daily visits (green area) against unique visitors (teal line).
                    </p>
                </div>

                <div class="flex items-center gap-4 text-xs font-bold shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-emerald-500" />
                        <span class="text-neutral-700 dark:text-neutral-300">Page Views</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-teal-400" />
                        <span class="text-neutral-700 dark:text-neutral-300">Unique Visitors</span>
                    </div>
                </div>
            </div>

            <!-- SVG Vector Chart -->
            <div class="mt-6 relative w-full overflow-x-auto scrollbar-none">
                <svg
                    class="w-full h-56 overflow-visible"
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient id="viewsGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.35" />
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Gridlines -->
                    <line x1="0" y1="20" :x2="chartWidth" y2="20" stroke="currentColor" class="text-neutral-200 dark:text-neutral-800" stroke-dasharray="4 4" />
                    <line x1="0" y1="80" :x2="chartWidth" y2="80" stroke="currentColor" class="text-neutral-200 dark:text-neutral-800" stroke-dasharray="4 4" />
                    <line x1="0" y1="140" :x2="chartWidth" y2="140" stroke="currentColor" class="text-neutral-200 dark:text-neutral-800" stroke-dasharray="4 4" />
                    <line x1="0" y1="200" :x2="chartWidth" y2="200" stroke="currentColor" class="text-neutral-200 dark:text-neutral-800" />

                    <!-- Views Area -->
                    <path :d="areaPathViews" fill="url(#viewsGradient)" />

                    <!-- Views Line -->
                    <polyline
                        fill="none"
                        stroke="#10b981"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :points="pointsViews"
                    />

                    <!-- Visitors Line -->
                    <polyline
                        fill="none"
                        stroke="#2dd4bf"
                        stroke-width="2.5"
                        stroke-dasharray="5 3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :points="pointsVisitors"
                    />
                </svg>

                <!-- Date Labels Below Chart -->
                <div class="mt-3 flex justify-between text-[10px] font-bold text-neutral-400 px-1">
                    <span v-for="(item, idx) in filteredTrend" :key="item.date" v-show="idx % Math.ceil(filteredTrend.length / 8) === 0 || idx === filteredTrend.length - 1">
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Top Pages & Traffic Breakdown (Visible in 'all', 'pages', 'devices') ── -->
        <div v-if="activeAnalyticsTab === 'all' || activeAnalyticsTab === 'pages' || activeAnalyticsTab === 'devices'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Top Visited Pages Table (Visible in 'all' and 'pages') -->
            <div
                v-if="activeAnalyticsTab === 'all' || activeAnalyticsTab === 'pages'"
                :class="activeAnalyticsTab === 'pages' ? 'lg:col-span-3' : 'lg:col-span-2'"
                class="rounded-3xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm overflow-hidden flex flex-col justify-between"
            >
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-neutral-100 dark:border-neutral-800">
                        <div>
                            <h3 class="text-base font-black text-neutral-900 dark:text-white flex items-center gap-2">
                                <Globe class="size-5 text-emerald-500" />
                                Most Visited Website Pages
                            </h3>
                            <p class="text-xs text-neutral-500 mt-0.5">Top performing routes and booking conversion rates.</p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-neutral-200 dark:border-neutral-800 text-neutral-400 font-extrabold uppercase tracking-wider text-[10px]">
                                    <th class="py-2.5 px-3">Page Name</th>
                                    <th class="py-2.5 px-3">Category</th>
                                    <th class="py-2.5 px-3 text-right">Views</th>
                                    <th class="py-2.5 px-3 text-right">Visitors</th>
                                    <th class="py-2.5 px-3 text-right">Booking Conv.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800 font-semibold">
                                <tr v-for="page in analytics.topPages" :key="page.name" class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/40 transition-colors">
                                    <td class="py-3 px-3">
                                        <p class="font-bold text-neutral-900 dark:text-white">{{ page.name }}</p>
                                        <p class="text-[10px] font-mono text-neutral-400 mt-0.5">{{ page.url }}</p>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[10px] font-bold text-neutral-600 dark:text-neutral-400">
                                            {{ page.category }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-neutral-900 dark:text-white tabular-nums">
                                        {{ formatNumber(page.views) }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-neutral-500 tabular-nums">
                                        {{ formatNumber(page.visitors) }}
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <span class="font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md">
                                            {{ page.conversion }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Device & Traffic Sources Widgets (Visible in 'all' and 'devices') -->
            <div v-if="activeAnalyticsTab === 'all' || activeAnalyticsTab === 'devices'" :class="activeAnalyticsTab === 'devices' ? 'lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6 space-y-0' : 'space-y-6'">
                <!-- Device Breakdown Card -->
                <div class="rounded-3xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                        <Smartphone class="size-4 text-emerald-500" />
                        Device Breakdown
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Platform visits by user device category.</p>

                    <div class="mt-4 space-y-3">
                        <div v-for="dev in analytics.deviceBreakdown" :key="dev.device">
                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                <span class="text-neutral-700 dark:text-neutral-300">{{ dev.device }}</span>
                                <span class="text-neutral-900 dark:text-white font-extrabold">{{ dev.percentage }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="dev.color"
                                    :style="{ width: `${dev.percentage}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Traffic Sources Card -->
                <div class="rounded-3xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                        <Compass class="size-4 text-teal-500" />
                        Traffic Sources
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Origin of incoming platform visitors.</p>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div v-for="src in analytics.sourcesBreakdown" :key="src.source" class="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/50 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">{{ src.source }}</p>
                            <p class="text-lg font-black text-neutral-900 dark:text-white mt-1">{{ src.percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
