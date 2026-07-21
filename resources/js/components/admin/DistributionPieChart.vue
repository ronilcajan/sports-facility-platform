<script setup lang="ts">
import { ref, computed } from 'vue';
import { PieChart as PieChartIcon } from '@lucide/vue';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

export interface SportBreakdownItem {
    label: string;
    count: number;
    revenue: number;
}

export interface StatusBreakdownItem {
    label: string;
    count: number;
}

const props = defineProps<{
    sportTypesBreakdown: SportBreakdownItem[];
    statusBreakdown: StatusBreakdownItem[];
}>();

const activeTab = ref<'sports' | 'statuses'>('sports');

const colors = [
    '#10b981', // emerald-500
    '#0284c7', // sky-600
    '#f59e0b', // amber-500
    '#a855f7', // purple-500
    '#f43f5e', // rose-500
    '#6366f1', // indigo-500
];

const currentItems = computed(() => {
    if (activeTab.value === 'sports') {
        return props.sportTypesBreakdown || [];
    }
    return props.statusBreakdown || [];
});

const totalCount = computed(() => {
    return currentItems.value.reduce((sum, item) => sum + item.count, 0);
});

const chartData = computed(() => {
    const labels = currentItems.value.map(item => item.label);
    const data = currentItems.value.map(item => item.count);

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: colors.slice(0, labels.length),
                borderColor: 'rgba(23, 23, 23, 0.8)',
                borderWidth: 2,
                hoverOffset: 6,
            },
        ],
    };
});

const chartOptions = computed(() => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(23, 23, 23, 0.95)',
                titleColor: '#f5f5f5',
                bodyColor: '#e5e5e5',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                padding: 10,
                callbacks: {
                    label: (context: any) => {
                        const val = context.parsed;
                        const pct = totalCount.value > 0 ? ((val / totalCount.value) * 100).toFixed(1) : 0;
                        return ` ${context.label}: ${val} (${pct}%)`;
                    },
                },
            },
        },
    };
});
</script>

<template>
    <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-md space-y-4 flex flex-col justify-between">
        <!-- Header -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-semibold text-neutral-400">
                    <PieChartIcon class="w-4 h-4 text-sky-500" />
                    <span>Distribution Chart</span>
                </div>

                <!-- Tab Toggle -->
                <div class="flex items-center p-1 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-xs font-bold border border-neutral-200 dark:border-neutral-700">
                    <button
                        @click="activeTab = 'sports'"
                        :class="[
                            'px-2.5 py-1 rounded-lg transition-all',
                            activeTab === 'sports' ? 'bg-white dark:bg-neutral-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white',
                        ]"
                    >
                        Sports
                    </button>
                    <button
                        @click="activeTab = 'statuses'"
                        :class="[
                            'px-2.5 py-1 rounded-lg transition-all',
                            activeTab === 'statuses' ? 'bg-white dark:bg-neutral-700 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-neutral-500 hover:text-neutral-900 dark:hover:text-white',
                        ]"
                    >
                        Status
                    </button>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-black text-neutral-900 dark:text-white tracking-tight">
                    {{ activeTab === 'sports' ? 'Sport Category Share' : 'Booking Status Breakdown' }}
                </h3>
                <p class="text-xs text-neutral-500">Percentage distribution across facility operations.</p>
            </div>
        </div>

        <!-- Donut Canvas & Central Badge -->
        <div class="relative h-56 w-full my-2 flex items-center justify-center">
            <Doughnut :data="chartData" :options="chartOptions" />
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">{{ totalCount }}</span>
                <span class="text-[10px] uppercase font-bold text-neutral-400">Total Units</span>
            </div>
        </div>

        <!-- Breakdown Legend Grid -->
        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-neutral-100 dark:border-neutral-800">
            <div
                v-for="(item, idx) in currentItems"
                :key="item.label"
                class="flex items-center justify-between px-3 py-2 rounded-xl bg-neutral-900/90 dark:bg-neutral-950/90 border border-neutral-800/80 shadow-sm text-xs"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <span
                        class="w-2.5 h-2.5 rounded-full shrink-0"
                        :style="{ backgroundColor: colors[idx % colors.length] }"
                    ></span>
                    <span class="truncate font-semibold text-neutral-200 dark:text-neutral-200">{{ item.label }}</span>
                </div>
                <span class="font-mono font-bold text-white dark:text-white ml-2">{{ item.count }}</span>
            </div>
        </div>
    </div>
</template>
