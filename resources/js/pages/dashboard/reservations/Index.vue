<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Ticket } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index as reservationsIndex } from '@/routes/dashboard/reservations';
import { show as reservationShow } from '@/routes/dashboard/reservations';

type Stats = {
    total: number;
    confirmed: number;
    pending: number;
    cancelled_or_expired: number;
    payments_completed: number;
    payments_pending: number;
    revenue_formatted: string;
};

type ManagedReservation = {
    id: number;
    reference: string;
    spectator_name: string;
    spectator_email: string;
    film_title: string;
    film_poster_url: string | null;
    seance_date_formatted: string;
    seance_time_range: string;
    salle_name: string;
    quantity: number;
    total_amount_formatted: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    reserved_at_formatted: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedReservations = {
    data: ManagedReservation[];
    links: PaginationLink[];
    current_page?: number;
    last_page?: number;
    total?: number;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

type Option = { value: string; label: string };
type SalleOption = { id: number; name: string };

type Filters = {
    search: string;
    reservation_status: string;
    payment_status: string;
    salle: string;
    reserved_date: string;
    seance_date: string;
    period: string;
};

const props = defineProps<{
    stats: Stats;
    reservations: PaginatedReservations;
    filters: Filters;
    salleOptions: SalleOption[];
    reservationStatusOptions: Option[];
    paymentStatusOptions: Option[];
    periodOptions: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'Gestion des réservations',
                href: reservationsIndex(),
            },
        ],
    },
});

const loading = ref(false);

const search = ref(props.filters.search);
const reservationStatusFilter = ref(
    props.filters.reservation_status || 'all',
);
const paymentStatusFilter = ref(props.filters.payment_status || 'all');
const salleFilter = ref(props.filters.salle || 'all');
const reservedDateFilter = ref(props.filters.reserved_date);
const seanceDateFilter = ref(props.filters.seance_date);
const periodFilter = ref(props.filters.period || 'all');

const reservationRows = computed(() => props.reservations.data ?? []);

const pagination = computed(() => ({
    currentPage:
        props.reservations.meta?.current_page ??
        props.reservations.current_page ??
        1,
    lastPage:
        props.reservations.meta?.last_page ??
        props.reservations.last_page ??
        1,
    total: props.reservations.meta?.total ?? props.reservations.total ?? 0,
    links: props.reservations.links ?? [],
}));

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30';

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        reservationStatusFilter.value = filters.reservation_status || 'all';
        paymentStatusFilter.value = filters.payment_status || 'all';
        salleFilter.value = filters.salle || 'all';
        reservedDateFilter.value = filters.reserved_date;
        seanceDateFilter.value = filters.seance_date;
        periodFilter.value = filters.period || 'all';
    },
);

function buildQuery(): Record<string, string | undefined> {
    return {
        search: search.value || undefined,
        reservation_status:
            reservationStatusFilter.value === 'all'
                ? undefined
                : reservationStatusFilter.value,
        payment_status:
            paymentStatusFilter.value === 'all'
                ? undefined
                : paymentStatusFilter.value,
        salle:
            salleFilter.value === 'all' ? undefined : salleFilter.value,
        reserved_date: reservedDateFilter.value || undefined,
        seance_date: seanceDateFilter.value || undefined,
        period:
            periodFilter.value === 'all' ? undefined : periodFilter.value,
    };
}

function applyFilters(): void {
    loading.value = true;
    router.get(reservationsIndex.url({ query: buildQuery() }), {}, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(
    [
        reservationStatusFilter,
        paymentStatusFilter,
        salleFilter,
        reservedDateFilter,
        seanceDateFilter,
        periodFilter,
    ],
    applyFilters,
);

function reservationBadgeVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'confirmed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'cancelled':
        case 'expired':
            return 'destructive';
        default:
            return 'outline';
    }
}

function paymentBadgeVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
}
</script>

<template>
    <Head title="Gestion des réservations" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <Heading
            title="Gestion des réservations"
            description="Suivez les réservations des spectateurs, les paiements simulés et l’état des billets."
        />

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <p
                v-for="card in [
                    {
                        label: 'Total réservations',
                        value: stats.total,
                    },
                    {
                        label: 'Confirmées',
                        value: stats.confirmed,
                    },
                    {
                        label: 'En attente',
                        value: stats.pending,
                    },
                    {
                        label: 'Annulées / expirées',
                        value: stats.cancelled_or_expired,
                    },
                    {
                        label: 'Paiements réussis',
                        value: stats.payments_completed,
                    },
                    {
                        label: 'Paiements en attente',
                        value: stats.payments_pending,
                    },
                    {
                        label: 'CA (simulation)',
                        value: stats.revenue_formatted,
                    },
                ]"
                :key="card.label"
                class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
            >
                <span
                    class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                    >{{ card.label }}</span
                >
                <span class="mt-2 block text-2xl font-semibold tabular-nums">{{
                    card.value
                }}</span>
            </p>
        </div>

        <div
            class="grid gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border lg:grid-cols-2 xl:grid-cols-3"
        >
            <div class="grid gap-2 xl:col-span-2">
                <Label for="search">Recherche</Label>
                <Input
                    id="search"
                    v-model="search"
                    type="search"
                    placeholder="Référence, spectateur, e-mail ou titre du film…"
                    autocomplete="off"
                />
            </div>
            <div class="grid gap-2">
                <Label for="reservation-status">Statut réservation</Label>
                <select
                    id="reservation-status"
                    v-model="reservationStatusFilter"
                    :class="selectClass"
                >
                    <option value="all">Tous</option>
                    <option
                        v-for="opt in reservationStatusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="payment-status">Statut paiement</Label>
                <select
                    id="payment-status"
                    v-model="paymentStatusFilter"
                    :class="selectClass"
                >
                    <option value="all">Tous</option>
                    <option
                        v-for="opt in paymentStatusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="salle-filter">Salle</Label>
                <select
                    id="salle-filter"
                    v-model="salleFilter"
                    :class="selectClass"
                >
                    <option value="all">Toutes</option>
                    <option
                        v-for="salle in salleOptions"
                        :key="salle.id"
                        :value="String(salle.id)"
                    >
                        {{ salle.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="reserved-date">Date de réservation</Label>
                <Input
                    id="reserved-date"
                    v-model="reservedDateFilter"
                    type="date"
                />
            </div>
            <div class="grid gap-2">
                <Label for="seance-date">Date de séance</Label>
                <Input
                    id="seance-date"
                    v-model="seanceDateFilter"
                    type="date"
                />
            </div>
            <div class="grid gap-2">
                <Label for="period">Période</Label>
                <select id="period" v-model="periodFilter" :class="selectClass">
                    <option value="all">Toutes</option>
                    <option
                        v-for="opt in periodOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
            </div>
        </div>

        <div
            class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div
                v-if="loading"
                class="absolute inset-0 z-20 flex items-center justify-center bg-background/60 backdrop-blur-[1px]"
            >
                <Spinner class="size-8" />
            </div>
            <div class="max-h-[min(70vh,42rem)] overflow-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead
                        class="sticky top-0 z-10 border-b bg-muted/95 text-muted-foreground shadow-sm backdrop-blur-sm [&_th]:bg-muted/95"
                    >
                        <tr>
                            <th class="px-3 py-3 font-medium">Réf.</th>
                            <th class="px-3 py-3 font-medium">Spectateur</th>
                            <th class="px-3 py-3 font-medium">Film</th>
                            <th class="px-3 py-3 font-medium">Séance</th>
                            <th class="px-3 py-3 font-medium">Salle</th>
                            <th class="px-3 py-3 font-medium">Places</th>
                            <th class="px-3 py-3 font-medium">Montant</th>
                            <th class="px-3 py-3 font-medium">Réservation</th>
                            <th class="px-3 py-3 font-medium">Paiement</th>
                            <th class="px-3 py-3 font-medium">Créée le</th>
                            <th class="px-3 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="reservationRows.length === 0">
                            <td
                                colspan="11"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <Ticket
                                    class="mx-auto mb-2 size-8 opacity-40"
                                />
                                Aucune réservation ne correspond à vos critères.
                            </td>
                        </tr>
                        <tr
                            v-for="row in reservationRows"
                            :key="row.id"
                            class="border-b last:border-0"
                        >
                            <td
                                class="px-3 py-3 font-mono text-xs font-medium"
                            >
                                {{ row.reference }}
                            </td>
                            <td class="max-w-[140px] px-3 py-3">
                                <p class="truncate font-medium">
                                    {{ row.spectator_name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ row.spectator_email }}
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded border bg-muted"
                                    >
                                        <img
                                            v-if="row.film_poster_url"
                                            :src="row.film_poster_url"
                                            :alt="row.film_title"
                                            class="size-full object-cover"
                                        />
                                        <Ticket
                                            v-else
                                            class="size-4 text-muted-foreground"
                                        />
                                    </div>
                                    <span
                                        class="max-w-[120px] truncate font-medium"
                                        >{{ row.film_title }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <p class="whitespace-nowrap text-xs">
                                    {{ row.seance_date_formatted }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ row.seance_time_range }}
                                </p>
                            </td>
                            <td class="px-3 py-3">{{ row.salle_name }}</td>
                            <td class="px-3 py-3 tabular-nums">
                                {{ row.quantity }}
                            </td>
                            <td class="px-3 py-3 font-medium tabular-nums">
                                {{ row.total_amount_formatted }}
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="
                                        reservationBadgeVariant(row.status)
                                    "
                                >
                                    {{ row.status_label }}
                                </Badge>
                            </td>
                            <td class="px-3 py-3">
                                <Badge
                                    :variant="
                                        paymentBadgeVariant(row.payment_status)
                                    "
                                >
                                    {{ row.payment_status_label }}
                                </Badge>
                            </td>
                            <td
                                class="whitespace-nowrap px-3 py-3 text-xs text-muted-foreground"
                            >
                                {{ row.reserved_at_formatted }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <Button as-child size="sm" variant="outline">
                                    <Link
                                        :href="
                                            reservationShow({
                                                reservation: row.id,
                                            })
                                        "
                                    >
                                        <Eye class="size-4" />
                                        Détails
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ pagination.total }} réservation(s) · page
            {{ pagination.currentPage }} / {{ pagination.lastPage }}
        </p>

        <nav
            v-if="pagination.links.length > 0"
            class="flex flex-wrap gap-1"
            aria-label="Pagination"
        >
            <template
                v-for="(link, index) in pagination.links"
                :key="index"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="rounded-md border px-3 py-1.5 transition-colors hover:bg-muted"
                    :class="
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground hover:bg-primary/90'
                            : 'border-border'
                    "
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
