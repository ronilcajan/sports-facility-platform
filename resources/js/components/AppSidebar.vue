<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import {
    Building,
    Calendar,
    CalendarDays,
    BarChart3,
    Users,
    UserCheck,
    LayoutGrid,
    Palette,
    Dumbbell,
    Settings,
    Gift,
} from '@lucide/vue';
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

type NavGroup = {
    label: string;
    items: NavItem[];
};

const navGroups = computed<NavGroup[]>(() => {
    if (!user.value) {
        return [
            {
                label: 'Platform',
                items: [
                    {
                        title: 'Dashboard',
                        href: dashboard(),
                        icon: LayoutGrid,
                    },
                ],
            },
        ];
    }

    if (user.value.can_manage_all_courts) {
        // Super admin: global management across every venue.
        if (user.value.is_super_admin) {
            return [
                {
                    label: 'Platform',
                    items: [
                        {
                            title: 'Dashboard',
                            href: '/admin/dashboard',
                            icon: LayoutGrid,
                        },
                        {
                            title: 'Bookings',
                            href: '/admin/bookings',
                            icon: CalendarDays,
                        },
                        {
                            title: 'Venues',
                            href: '/admin/venues',
                            icon: Building,
                        },
                        {
                            title: 'Courts',
                            href: '/admin/courts',
                            icon: Dumbbell,
                        },
                        {
                            title: 'Customers',
                            href: '/admin/users',
                            icon: Users,
                        },
                        {
                            title: 'Freebies & Rewards',
                            href: '/admin/rewards',
                            icon: Gift,
                        },
                    ],
                },
                {
                    label: 'Systems',
                    items: [
                        {
                            title: 'Users',
                            href: '/admin/staff',
                            icon: UserCheck,
                        },
                        {
                            title: 'Reports',
                            href: '/admin/reports',
                            icon: BarChart3,
                        },
                        {
                            title: 'Appearance',
                            href: '/admin/appearance',
                            icon: Palette,
                        },
                    ],
                },
            ];
        }

        // Venue admin: scoped to their assigned venue (no venue list; a Setting
        // page edits their own venue instead).
        return [
            {
                label: 'Platform',
                items: [
                    {
                        title: 'Dashboard',
                        href: '/admin/dashboard',
                        icon: LayoutGrid,
                    },
                    {
                        title: 'Bookings',
                        href: '/admin/bookings',
                        icon: CalendarDays,
                    },
                    { title: 'Courts', href: '/admin/courts', icon: Dumbbell },
                    {
                        title: 'Customers',
                        href: '/admin/users?role=customer',
                        icon: Users,
                    },
                    {
                        title: 'Freebies & Rewards',
                        href: '/admin/rewards',
                        icon: Gift,
                    },
                ],
            },
            {
                label: 'Systems',
                items: [
                    { title: 'Users', href: '/admin/users', icon: UserCheck },
                    {
                        title: 'Reports',
                        href: '/admin/reports',
                        icon: BarChart3,
                    },
                    {
                        title: 'Setting',
                        href: '/admin/settings',
                        icon: Settings,
                    },
                ],
            },
        ];
    }

    if (user.value.is_staff) {
        return [
            {
                label: 'Platform',
                items: [
                    {
                        title: 'Dashboard',
                        href: '/staff/dashboard',
                        icon: LayoutGrid,
                    },
                    {
                        title: 'Bookings',
                        href: '/staff/bookings',
                        icon: CalendarDays,
                    },
                    {
                        title: 'Courts',
                        href: '/staff/courts',
                        icon: Dumbbell,
                    },
                ],
            },
            {
                label: 'Systems',
                items: [
                    {
                        title: 'Schedules',
                        href: '/staff/schedules',
                        icon: Calendar,
                    },
                    {
                        title: 'Reports',
                        href: '/staff/reports',
                        icon: BarChart3,
                    },
                ],
            },
        ];
    }

    // Customers: their own dashboard and bookings; they can book at any venue.
    return [
        {
            label: 'Platform',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                },
                {
                    title: 'Bookings',
                    href: '/my-bookings',
                    icon: CalendarDays,
                },
                {
                    title: 'Rewards & Points',
                    href: '/customer/rewards',
                    icon: Gift,
                },
            ],
        },
    ];
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
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
            <NavMain
                v-for="group in navGroups"
                :key="group.label"
                :items="group.items"
                :label="group.label"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
