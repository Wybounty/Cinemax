<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Download, Mail, Timer } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ReservationController from '@/actions/App/Http/Controllers/ReservationController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { downloadTickets } from '@/routes/reservations';
import {
    index as myReservationsIndex,
    show as myReservationShow,
} from '@/routes/dashboard/my-reservations';

type ReservationDetail = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    film_title: string;
    film_poster_url: string | null;
    film_synopsis: string | null;
    seance_date_formatted: string;
    seance_time_range: string;
    seance_start_time: string;
    seance_end_time: string;
    salle_name: string;
    quantity: number;
    unit_price_formatted: string;
    total_amount_formatted: string;
    reserved_at_formatted: string;
    expires_at: string | null;
    expires_at_formatted: string | null;
    can_pay: boolean;
    can_cancel: boolean;
    can_download_tickets: boolean;
    billets_count: number;
};

type BilletRow = {
    id: number;
    ticket_code: string;
    status: string;
    status_label: string;
    issued_at_formatted: string;
};

const props = defineProps<{
    reservation: ReservationDetail;
    billets: BilletRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Mes réservations', href: myReservationsIndex() },
            {
                title: props.reservation.reference,
                href: myReservationShow({ reservation: props.reservation.id }),
            },
        ],
    },
});

const cancelling = ref(false);
const resending = ref(false);
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

const checkoutHref = computed(() =>
    ReservationController.checkout.url({ reservation: props.reservation.id }),
);

const downloadHref = computed(() =>
    downloadTickets.url({ reservation: props.reservation.id }),
);

const secondsLeft = computed(() => {
    if (!props.reservation.expires_at) {
        return null;
    }

    return Math.max(
        0,
        Math.floor(
            (new Date(props.reservation.expires_at).getTime() - now.value) /
                1000,
        ),
    );
});

const countdownLabel = computed(() => {
    if (secondsLeft.value === null) {
        return null;
    }

    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;

    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

function cancelReservation(): void {
    cancelling.value = true;
    router.post(
        ReservationController.cancel.url({
            reservation: props.reservation.id,
        }),
        {},
        { onFinish: () => (cancelling.value = false) },
    );
}

function resendTicketsEmail(): void {
    resending.value = true;
    router.post(
        ReservationController.resendTickets.url({
            reservation: props.reservation.id,
        }),
        {},
        { onFinish: () => (resending.value = false) },
    );
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
</script>

<template>
    <Head :title="`Réservation ${reservation.reference}`" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link
                    :href="myReservationsIndex()"
                    class="mb-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Retour à mes réservations
                </Link>
                <Heading
                    :title="reservation.film_title"
                    :description="`Référence ${reservation.reference}`"
                />
            </div>
            <div class="flex flex-wrap gap-2">
                <Badge :variant="reservationBadgeVariant(reservation.status)">
                    {{ reservation.status_label }}
                </Badge>
                <Badge
                    :variant="paymentBadgeVariant(reservation.payment_status)"
                >
                    {{ reservation.payment_status_label }}
                </Badge>
            </div>
        </div>

        <div
            v-if="
                reservation.status === 'pending' &&
                reservation.expires_at &&
                countdownLabel
            "
            class="flex items-center gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm"
        >
            <Timer class="size-4 shrink-0" />
            <span v-if="secondsLeft !== null && secondsLeft > 0">
                Temps restant pour payer :
                <strong class="tabular-nums">{{ countdownLabel }}</strong>
            </span>
            <span v-else class="font-medium text-destructive">
                Délai de paiement dépassé.
            </span>
        </div>

        <p
            v-else-if="reservation.status === 'expired'"
            class="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm"
        >
            Cette réservation a expiré. Les places ne sont plus bloquées.
        </p>
        <p
            v-else-if="reservation.status === 'cancelled'"
            class="rounded-lg border px-4 py-3 text-sm text-muted-foreground"
        >
            Réservation annulée.
        </p>

        <div class="grid gap-6 lg:grid-cols-3">
            <div
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border lg:col-span-1"
            >
                <img
                    v-if="reservation.film_poster_url"
                    :src="reservation.film_poster_url"
                    :alt="reservation.film_title"
                    class="aspect-[2/3] w-full object-cover"
                />
                <div
                    v-else
                    class="flex aspect-[2/3] items-center justify-center bg-muted text-muted-foreground"
                >
                    Pas d’affiche
                </div>
            </div>

            <div
                class="space-y-6 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border lg:col-span-2"
            >
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Séance
                    </h2>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Date</dt>
                            <dd>{{ reservation.seance_date_formatted }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Horaire</dt>
                            <dd>
                                {{ reservation.seance_start_time }} –
                                {{ reservation.seance_end_time }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Salle</dt>
                            <dd>{{ reservation.salle_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Prix unitaire</dt>
                            <dd>{{ reservation.unit_price_formatted }}</dd>
                        </div>
                    </dl>
                </div>

                <p
                    v-if="reservation.film_synopsis"
                    class="text-sm text-muted-foreground"
                >
                    {{ reservation.film_synopsis }}
                </p>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Réservation & paiement
                    </h2>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Places</dt>
                            <dd>{{ reservation.quantity }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Total</dt>
                            <dd class="font-semibold">
                                {{ reservation.total_amount_formatted }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Réservé le</dt>
                            <dd>{{ reservation.reserved_at_formatted }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Paiement</dt>
                            <dd>{{ reservation.payment_status_label }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <section
            class="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Billets ({{ billets.length }})
            </h2>
            <ul v-if="billets.length" class="mt-4 space-y-2">
                <li
                    v-for="billet in billets"
                    :key="billet.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm"
                >
                    <span class="font-mono">{{ billet.ticket_code }}</span>
                    <span class="text-muted-foreground">
                        {{ billet.status_label }} ·
                        {{ billet.issued_at_formatted }}
                    </span>
                </li>
            </ul>
            <p v-else class="mt-4 text-sm text-muted-foreground">
                Aucun billet émis tant que le paiement n’est pas confirmé.
            </p>
        </section>

        <div class="flex flex-wrap gap-3">
            <Button
                v-if="reservation.can_download_tickets"
                as-child
                size="lg"
                class="gap-2"
            >
                <a
                    :href="downloadHref"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <Download class="size-4" />
                    Télécharger mes billets
                </a>
            </Button>
            <Button
                v-if="reservation.can_download_tickets"
                type="button"
                variant="outline"
                class="gap-2"
                :disabled="resending"
                @click="resendTicketsEmail"
            >
                <Spinner v-if="resending" />
                <Mail v-else class="size-4" />
                Renvoyer l’e-mail
            </Button>
            <Button v-if="reservation.can_pay" as-child size="lg">
                <Link :href="checkoutHref">Payer maintenant</Link>
            </Button>
            <Button
                v-if="reservation.can_cancel"
                type="button"
                variant="outline"
                :disabled="cancelling"
                @click="cancelReservation"
            >
                <Spinner v-if="cancelling" />
                Annuler la réservation
            </Button>
        </div>
    </div>
</template>
