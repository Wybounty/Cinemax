<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarClock,
    Clapperboard,
    LayoutGrid,
    Ticket,
    Theater,
    TicketCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
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
import { index as filmsIndex } from '@/routes/dashboard/films';
import { index as myReservationsIndex } from '@/routes/dashboard/my-reservations';
import { index as reservationsIndex } from '@/routes/dashboard/reservations';
import { index as seancesIndex } from '@/routes/dashboard/seances';
import { index as sallesIndex } from '@/routes/dashboard/salles';
import { index as usersIndex } from '@/routes/dashboard/users';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => {
    const slug = page.props.auth.user?.role?.slug;

    return slug === 'admin' || slug === 'super_admin';
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Mes réservations',
            href: myReservationsIndex(),
            icon: TicketCheck,
        },
    ];

    if (isAdmin.value) {
        items.push(
            {
                title: 'Gestion des films',
                href: filmsIndex(),
                icon: Clapperboard,
            },
            {
                title: 'Gestion des séances',
                href: seancesIndex(),
                icon: CalendarClock,
            },
            {
                title: 'Gestion des réservations',
                href: reservationsIndex(),
                icon: Ticket,
            },
            {
                title: 'Gestion des salles',
                href: sallesIndex(),
                icon: Theater,
            },
            {
                title: 'Utilisateurs',
                href: usersIndex(),
                icon: Users,
            },
        );
    }

    return items;
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
