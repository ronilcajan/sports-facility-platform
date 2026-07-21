<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import {
    CalendarRange,
    Clock,
    CheckCircle,
    DollarSign,
    TrendingUp,
} from '@lucide/vue';

export interface DashboardStats {
    total: number;
    pending: number;
    confirmed: number;
    totalSpent: string;
}

const props = defineProps<{
    stats: DashboardStats;
}>();

const currentSlide = ref(0);
const touchStartX = ref(0);
const touchEndX = ref(0);
const isPaused = ref(false);
let autoSlideInterval: ReturnType<typeof setInterval> | null = null;

const slidesCount = 4;

function nextSlide() {
    currentSlide.value = (currentSlide.value + 1) % slidesCount;
}

function prevSlide() {
    currentSlide.value = (currentSlide.value - 1 + slidesCount) % slidesCount;
}

function goToSlide(index: number) {
    currentSlide.value = index;
}

function handleTouchStart(e: TouchEvent) {
    touchStartX.value = e.touches[0].clientX;
    isPaused.value = true;
}

function handleTouchEnd(e: TouchEvent) {
    touchEndX.value = e.changedTouches[0].clientX;
    const diff = touchStartX.value - touchEndX.value;

    if (Math.abs(diff) > 40) {
        if (diff > 0) {
            nextSlide();
        } else {
            prevSlide();
        }
    }

    setTimeout(() => {
        isPaused.value = false;
    }, 2000);
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        if (!isPaused.value) {
            nextSlide();
        }
    }, 4500);
}

onMounted(() => {
    startAutoSlide();
});

onUnmounted(() => {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
});
</script>

<template>
    <div class="relative w-full">
        <!-- Desktop Grid View (md:grid) -->
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat card: Total bookings -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <CalendarRange class="w-12 h-12 text-neutral-600 dark:text-neutral-400" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Bookings</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">{{ stats.total }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-neutral-500 font-medium">
                    <span>Active and completed reservations</span>
                </div>
            </div>

            <!-- Stat card: Pending approval -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <Clock class="w-12 h-12 text-amber-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Pending Approval</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block">{{ stats.pending }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-amber-600 font-medium">
                    <span v-if="stats.pending > 0" class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span> Awaiting staff confirmation
                    </span>
                    <span v-else>All bookings processed</span>
                </div>
            </div>

            <!-- Stat card: Confirmed -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <CheckCircle class="w-12 h-12 text-emerald-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Confirmed Bookings</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ stats.confirmed }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-emerald-600 font-medium">
                    <span>Approved reservations ready for play</span>
                </div>
            </div>

            <!-- Stat card: Total spent -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <DollarSign class="w-12 h-12 text-emerald-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Investment</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">₱{{ stats.totalSpent }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-neutral-500 font-medium">
                    <TrendingUp class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Includes approved & pending payments</span>
                </div>
            </div>
        </div>

        <!-- Mobile Slideshow View (< md) -->
        <div
            class="md:hidden space-y-3"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
        >
            <div class="relative overflow-hidden rounded-3xl border border-neutral-200/80 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-lg min-h-[140px]">
                <!-- Smooth horizontal sliding track -->
                <div
                    class="flex transition-transform duration-500 ease-in-out w-full"
                    :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
                >
                    <!-- Slide 0: Total Bookings -->
                    <div class="w-full shrink-0 p-6 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-neutral-400 uppercase tracking-wider">Total Bookings</span>
                            <div class="p-2 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                <CalendarRange class="w-6 h-6" />
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-neutral-900 dark:text-white tracking-tight">{{ stats.total }}</span>
                            <p class="text-xs text-neutral-500 mt-1 font-medium">Active and completed reservations</p>
                        </div>
                    </div>

                    <!-- Slide 1: Pending Approval -->
                    <div class="w-full shrink-0 p-6 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-amber-500 dark:text-amber-400 uppercase tracking-wider">Pending Approval</span>
                            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600">
                                <Clock class="w-6 h-6" />
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ stats.pending }}</span>
                            <div class="flex items-center gap-1.5 mt-1 text-xs text-amber-600 font-medium">
                                <span v-if="stats.pending > 0" class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span> Awaiting staff confirmation
                                </span>
                                <span v-else>All bookings processed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Confirmed Bookings -->
                    <div class="w-full shrink-0 p-6 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Confirmed Bookings</span>
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                                <CheckCircle class="w-6 h-6" />
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ stats.confirmed }}</span>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">Approved reservations ready for play</p>
                        </div>
                    </div>

                    <!-- Slide 3: Total Investment -->
                    <div class="w-full shrink-0 p-6 flex flex-col justify-between space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-neutral-400 uppercase tracking-wider">Total Investment</span>
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                                <DollarSign class="w-6 h-6" />
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-neutral-900 dark:text-white tracking-tight">₱{{ stats.totalSpent }}</span>
                            <div class="flex items-center gap-1 mt-1 text-xs text-neutral-500 font-medium">
                                <TrendingUp class="w-3.5 h-3.5 text-emerald-600" />
                                <span>Includes approved & pending payments</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slideshow Pagination Dots -->
            <div class="flex items-center justify-center gap-2 pt-1">
                <button
                    v-for="(_, idx) in slidesCount"
                    :key="idx"
                    @click="goToSlide(idx)"
                    class="h-2 rounded-full transition-all duration-300"
                    :class="[
                        currentSlide === idx
                            ? 'w-6 bg-emerald-600 shadow-sm shadow-emerald-600/50'
                            : 'w-2 bg-neutral-300 dark:bg-neutral-700 hover:bg-neutral-400',
                    ]"
                    :aria-label="`Go to slide ${idx + 1}`"
                ></button>
            </div>
        </div>
    </div>
</template>
