<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clapperboard, Download, Timer } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { downloadTickets } from '@/routes/reservations';
import {
    index as myReservationsIndex,
    show as myReservationShow,
} from '@/routes/dashboard/my-reservations';

type ReservationRow = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    film_title: string;
    film_poster_url: string | null;
    seance_date_formatted: string;
    seance_time_range: string;
    salle_name: string;
    quantity: number;
    total_amount_formatted: string;
    reserved_at_formatted: string;
    expires_at: string | null;
    can_download_tickets: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedReservations = {
    data: ReservationRow[];
    links: PaginationLink[];
    meta?: { current_page: number; last_page: number; total: number };
};

const props = defineProps<{
    reservations: PaginatedReservations;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Mes réservations', href: myReservationsIndex() },
        ],
    },
});

const rows = computed(() => props.reservations.data ?? []);

const pagination = computed(() => ({
    links: props.reservations.links ?? [],
    total: props.reservations.meta?.total ?? rows.value.length,
    currentPage: props.reservations.meta?.current_page ?? 1,
    lastPage: props.reservations.meta?.last_page ?? 1,
}));

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

function secondsLeft(expiresAt: string | null): number | null {
    if (!expiresAt) {
        return null;
    }

    return Math.max(
        0,
        Math.floor((new Date(expiresAt).getTime() - now.value) / 1000),
    );
}

function countdownLabel(expiresAt: string | null): string | null {
    const left = secondsLeft(expiresAt);

    if (left === null) {
        return null;
    }

    const m = Math.floor(left / 60);
    const s = left % 60;

    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

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

function downloadHref(id: number): string {
    return downloadTickets.url({ reservation: id });
}
</script>

<template>
    <Head title="Mes réservations" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <Heading
            title="Mes réservations"
            description="Retrouvez l’historique de vos séances, le statut de paiement et téléchargez vos billets numériques."
        />

        <div
            v-if="rows.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-20 text-center dark:border-sidebar-border"
        >
            <Clapperboard class="mb-4 size-12 text-muted-foreground/50" />
            <p class="text-lg font-medium">Aucune réservation</p>
            <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                Parcourez le catalogue et réservez votre prochaine séance au
                cinéma.
            </p>
            <Button as-child class="mt-6" variant="outline">
                <Link href="/film">Voir les films</Link>
            </Button>
        </div>

        <ul v-else class="grid gap-4 lg:grid-cols-2">
            <li
                v-for="reservation in rows"
                :key="reservation.id"
                class="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div class="flex gap-4 p-4">
                    <div
                        class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                    >
                        <img
                            v-if="reservation.film_poster_url"
                            :src="reservation.film_poster_url"
                            :alt="reservation.film_title"
                            class="size-full object-cover"
                        />
                        <Clapperboard
                            v-else
                            class="size-8 text-muted-foreground"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-semibold">
                            {{ reservation.film_title }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ reservation.seance_date_formatted }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ reservation.seance_time_range }} ·
                            {{ reservation.salle_name }}
                        </p>
                        <p class="mt-2 font-mono text-xs text-muted-foreground">
                            {{ reservation.reference }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2 border-t px-4 py-3 text-sm"
                >
                    <Badge
                        :variant="
                            reservationBadgeVariant(reservation.status)
                        "
                    >
                        {{ reservation.status_label }}
                    </Badge>
                    <Badge
                        :variant="
                            paymentBadgeVariant(reservation.payment_status)
                        "
                    >
                        {{ reservation.payment_status_label }}
                    </Badge>
                    <span
                        v-if="
                            reservation.status === 'pending' &&
                            countdownLabel(reservation.expires_at)
                        "
                        class="inline-flex items-center gap-1 text-xs text-amber-700 dark:text-amber-400"
                    >
                        <Timer class="size-3.5" />
                        <template
                            v-if="secondsLeft(reservation.expires_at)! > 0"
                        >
                            Expire dans
                            {{ countdownLabel(reservation.expires_at) }}
                        </template>
                        <template v-else> Délai dépassé </template>
                    </span>
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-t bg-muted/30 px-4 py-3"
                >
                    <div class="text-sm">
                        <span class="text-muted-foreground"
                            >{{ reservation.quantity }} place(s) ·</span
                        >
                        <span class="font-medium">
                            {{ reservation.total_amount_formatted }}
                        </span>
                        <span
                            class="mt-0.5 block text-xs text-muted-foreground"
                        >
                            Réservé le {{ reservation.reserved_at_formatted }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button as-child size="sm" variant="outline">
                            <Link
                                :href="
                                    myReservationShow({
                                        reservation: reservation.id,
                                    })
                                "
                            >
                                Détail
                            </Link>
                        </Button>
                        <Button
                            v-if="reservation.can_download_tickets"
                            as-child
                            size="sm"
                            class="gap-1"
                        >
                            <a
                                :href="downloadHref(reservation.id)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Download class="size-4" />
                                Télécharger
                            </a>
                        </Button>
                    </div>
                </div>
            </li>
        </ul>

        <template v-if="pagination.lastPage > 1">
            <p class="text-sm text-muted-foreground">
                {{ pagination.total }} réservation(s) · page
                {{ pagination.currentPage }} / {{ pagination.lastPage }}
            </p>
            <nav class="flex flex-wrap gap-1" aria-label="Pagination">
                <template
                    v-for="(link, index) in pagination.links"
                    :key="index"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-muted"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border'
                        "
                        v-html="link.label"
                    />
                </template>
            </nav>
        </template>
    </div>
</template>
