<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarClock,
    Clapperboard,
    Ticket,
    Theater,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';
import { index as reservationsAdminIndex } from '@/routes/dashboard/reservations';
import { show as adminReservationShow } from '@/routes/dashboard/reservations';
import { index as myReservationsIndex } from '@/routes/dashboard/my-reservations';

type PeriodOption = { value: string; label: string };

type DayPoint = { date: string; label: string; value: number };

type RevenuePoint = DayPoint & { value_formatted: string };

type StatsPayload = {
    period: string;
    period_label: string;
    cards: {
        films_total: number;
        films_active: number;
        seances_total: number;
        seances_upcoming: number;
        salles_available: number;
        reservations_total: number;
        reservations_confirmed: number;
        tickets_sold: number;
        revenue: number;
        revenue_formatted: string;
    };
    charts: {
        reservations_per_day: DayPoint[];
        tickets_per_day: DayPoint[];
        revenue_per_day: RevenuePoint[];
        average_fill_rate_percent: number;
    };
    recent_reservations: Array<{
        id: number;
        reference: string;
        client: string;
        email: string;
        film_title: string;
        seance_label: string;
        tickets: number;
        total_formatted: string;
        status: string;
        status_label: string;
    }>;
};

const props = defineProps<{
    is_admin: boolean;
    stats: StatsPayload | null;
    period: string;
    period_options: PeriodOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const periodFilter = ref(props.period);

watch(
    () => props.period,
    (value) => {
        periodFilter.value = value;
    },
);

watch(periodFilter, (value) => {
    if (value === props.period) {
        return;
    }

    router.get(
        dashboard.url({ query: { period: value } }),
        {},
        { preserveState: true, replace: true, preserveScroll: true },
    );
});

const selectClass =
    'flex h-9 w-full max-w-xs rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30';

function seriesBarHeight(value: number, series: { value: number }[]): number {
    const max = Math.max(1, ...series.map((point) => point.value));

    return Math.round((value / max) * 100);
}

const hasChartData = computed(() => {
    if (!props.stats) {
        return false;
    }

    const { charts } = props.stats;

    return (
        charts.reservations_per_day.some((d) => d.value > 0) ||
        charts.tickets_per_day.some((d) => d.value > 0) ||
        charts.revenue_per_day.some((d) => d.value > 0)
    );
});

function statusVariant(
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
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <template v-if="!is_admin">
            <Heading
                title="Bienvenue"
                description="Retrouvez vos réservations et le catalogue des films."
            />
            <div class="flex flex-wrap gap-3">
                <Button as-child>
                    <Link :href="myReservationsIndex()">Mes réservations</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link href="/film">Voir les films</Link>
                </Button>
            </div>
            <div
                class="relative min-h-48 flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </template>

        <template v-else-if="stats">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <Heading
                    title="Tableau de bord"
                    description="Vue d’ensemble Cinemax — données en temps réel."
                />
                <div class="grid gap-2">
                    <Label for="period">Période</Label>
                    <select
                        id="period"
                        v-model="periodFilter"
                        :class="selectClass"
                    >
                        <option
                            v-for="opt in period_options"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Filtre actif : {{ stats.period_label }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="card in [
                        {
                            label: 'Films enregistrés',
                            value: stats.cards.films_total,
                            icon: Clapperboard,
                        },
                        {
                            label: 'Films actifs (publiés)',
                            value: stats.cards.films_active,
                            icon: Clapperboard,
                        },
                        {
                            label: 'Séances programmées',
                            value: stats.cards.seances_total,
                            icon: CalendarClock,
                        },
                        {
                            label: 'Séances à venir',
                            value: stats.cards.seances_upcoming,
                            icon: CalendarClock,
                        },
                        {
                            label: 'Salles disponibles',
                            value: stats.cards.salles_available,
                            icon: Theater,
                        },
                        {
                            label: 'Réservations (période)',
                            value: stats.cards.reservations_total,
                            icon: Ticket,
                        },
                        {
                            label: 'Réservations confirmées',
                            value: stats.cards.reservations_confirmed,
                            icon: Ticket,
                        },
                        {
                            label: 'Billets vendus',
                            value: stats.cards.tickets_sold,
                            icon: Ticket,
                        },
                        {
                            label: 'Chiffre d’affaires',
                            value: stats.cards.revenue_formatted,
                            icon: TrendingUp,
                        },
                    ]"
                    :key="card.label"
                    class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <component :is="card.icon" class="size-4" />
                        <span
                            class="text-xs font-medium uppercase tracking-wide"
                            >{{ card.label }}</span
                        >
                    </div>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">
                        {{ card.value }}
                    </p>
                </article>
            </div>

            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <h2 class="text-base font-semibold">Fréquentation</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Taux de remplissage moyen des salles (période) :
                    <strong>{{ stats.charts.average_fill_rate_percent }} %</strong>
                </p>

                <p
                    v-if="!hasChartData"
                    class="mt-8 text-center text-sm text-muted-foreground"
                >
                    Aucune activité confirmée sur cette période.
                </p>

                <div
                    v-else
                    class="mt-6 grid gap-8 lg:grid-cols-3"
                >
                    <div
                        v-for="block in [
                            {
                                title: 'Réservations / jour',
                                series: stats.charts.reservations_per_day,
                            },
                            {
                                title: 'Billets vendus / jour',
                                series: stats.charts.tickets_per_day,
                            },
                            {
                                title: 'Chiffre d’affaires / jour',
                                series: stats.charts.revenue_per_day,
                            },
                        ]"
                        :key="block.title"
                    >
                        <h3 class="text-sm font-medium">{{ block.title }}</h3>
                        <div
                            class="mt-4 flex h-40 items-end gap-1 border-b border-border pb-1"
                        >
                            <div
                                v-for="point in block.series"
                                :key="point.date"
                                class="group relative flex flex-1 flex-col items-center justify-end"
                                :title="
                                    'value' in point && 'value_formatted' in point
                                        ? (point as RevenuePoint).value_formatted
                                        : String(point.value)
                                "
                            >
                                <div
                                    class="w-full max-w-3 rounded-t bg-primary/80 transition-colors group-hover:bg-primary"
                                    :style="{
                                        height: `${seriesBarHeight(point.value, block.series)}%`,
                                        minHeight:
                                            point.value > 0 ? '4px' : '0',
                                    }"
                                />
                                <span
                                    class="mt-1 hidden text-[10px] text-muted-foreground sm:block"
                                    >{{ point.label }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h2 class="text-base font-semibold">Activité récente</h2>
                    <Button as-child size="sm" variant="outline">
                        <Link :href="reservationsAdminIndex()">
                            Gestion des réservations
                        </Link>
                    </Button>
                </div>

                <p
                    v-if="stats.recent_reservations.length === 0"
                    class="mt-6 text-sm text-muted-foreground"
                >
                    Aucune réservation enregistrée pour le moment.
                </p>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">Réf.</th>
                                <th class="px-3 py-2 font-medium">Client</th>
                                <th class="px-3 py-2 font-medium">Film</th>
                                <th class="px-3 py-2 font-medium">Séance</th>
                                <th class="px-3 py-2 font-medium">Billets</th>
                                <th class="px-3 py-2 font-medium">Montant</th>
                                <th class="px-3 py-2 font-medium">Statut</th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in stats.recent_reservations"
                                :key="row.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2 font-mono text-xs">
                                    {{ row.reference }}
                                </td>
                                <td class="px-3 py-2">
                                    <p class="font-medium">{{ row.client }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ row.email }}
                                    </p>
                                </td>
                                <td class="px-3 py-2">{{ row.film_title }}</td>
                                <td class="px-3 py-2 text-xs text-muted-foreground">
                                    {{ row.seance_label }}
                                </td>
                                <td class="px-3 py-2 tabular-nums">
                                    {{ row.tickets }}
                                </td>
                                <td class="px-3 py-2 font-medium tabular-nums">
                                    {{ row.total_formatted }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge :variant="statusVariant(row.status)">
                                        {{ row.status_label }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Button as-child size="sm" variant="ghost">
                                        <Link
                                            :href="
                                                adminReservationShow({
                                                    reservation: row.id,
                                                })
                                            "
                                        >
                                            Voir
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>
