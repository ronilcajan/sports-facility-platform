<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { BookOpen, Calendar, CalendarDays, BarChart3, Users, UserCheck, Shield, LayoutGrid, Palette, Dumbbell } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user as any);

const mainNavItems = computed<NavItem[]>(() => {
    if (!user.value) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];
    }

    if (user.value.can_manage_all_courts) {
        return [
            {
                title: 'Super Admin Overview',
                href: '/admin/dashboard',
                icon: Shield,
            },
            {
                title: 'Courts Management',
                href: '/admin/courts',
                icon: Dumbbell,
            },
            {
                title: 'All Bookings',
                href: '/admin/bookings',
                icon: CalendarDays,
            },
            {
                title: 'Court Staff',
                href: '/admin/staff',
                icon: UserCheck,
            },
            {
                title: 'User Accounts',
                href: '/admin/users',
                icon: Users,
            },
            {
                title: 'System Reports',
                href: '/admin/reports',
                icon: BarChart3,
            },
            {
                title: 'Appearance',
                href: '/admin/appearance',
                icon: Palette,
            },
        ];
    }

    if (user.value.is_staff) {
        return [
            {
                title: 'Staff Dashboard',
                href: '/staff/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Court Bookings',
                href: '/staff/bookings',
                icon: CalendarDays,
            },
            {
                title: 'Schedule & Blackouts',
                href: '/staff/schedules',
                icon: Calendar,
            },
            {
                title: 'Court Reports',
                href: '/staff/reports',
                icon: BarChart3,
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
