<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Download, Mail, Ticket } from '@lucide/vue';
import { ref } from 'vue';
import ReservationController from '@/actions/App/Http/Controllers/ReservationController';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index as reservationsIndex } from '@/routes/dashboard/my-reservations';
import { downloadTickets } from '@/routes/reservations';

type CheckoutReservation = {
    id: number;
    reference: string;
    quantity: number;
    total_amount_formatted: string;
    film: { title: string; poster_url: string | null };
    seance: { date_formatted: string; time_range: string };
    salle_name: string;
};

type BilletStub = { id: number; ticket_code: string };

const props = defineProps<{
    reservation: CheckoutReservation;
    billets: BilletStub[];
    is_guest: boolean;
    can_download_tickets: boolean;
    can_resend_email: boolean;
    tickets_email_sent_at: string | null;
    payment_status_label: string;
}>();

const resending = ref(false);

const downloadHref = downloadTickets.url({
    reservation: props.reservation.id,
});

function resendEmail(): void {
    resending.value = true;
    router.post(
        ReservationController.resendTickets.url({
            reservation: props.reservation.id,
        }),
        {},
        { onFinish: () => (resending.value = false) },
    );
}
</script>

<template>
    <Head title="Réservation confirmée" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer class="max-w-2xl text-center">
                <CheckCircle2
                    class="mx-auto size-14 text-emerald-600 dark:text-emerald-400"
                />
                <h1 class="mt-4 text-3xl font-semibold">C’est confirmé !</h1>
                <p class="mt-2 text-muted-foreground">
                    Référence {{ reservation.reference }} — {{ billets.length }}
                    billet(s) émis · {{ payment_status_label }}
                </p>
                <p class="mt-3 text-sm text-muted-foreground">
                    Un e-mail avec le PDF de vos billets (QR codes scannables,
                    démonstration) a été envoyé à l’adresse indiquée au paiement.
                </p>

                <div
                    class="mx-auto mt-8 max-w-md rounded-xl border p-6 text-left text-sm"
                >
                    <p class="font-medium">{{ reservation.film.title }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ reservation.seance.date_formatted }} ·
                        {{ reservation.seance.time_range }}
                    </p>
                    <p>{{ reservation.salle_name }}</p>
                    <p class="mt-3 font-semibold">
                        {{ reservation.total_amount_formatted }}
                    </p>
                    <ul
                        v-if="billets.length"
                        class="mt-4 space-y-1 font-mono text-xs"
                    >
                        <li v-for="billet in billets" :key="billet.id">
                            {{ billet.ticket_code }}
                        </li>
                    </ul>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Button
                        v-if="can_download_tickets"
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
                        v-if="can_resend_email"
                        type="button"
                        variant="outline"
                        class="gap-2"
                        :disabled="resending"
                        @click="resendEmail"
                    >
                        <Spinner v-if="resending" />
                        <Mail v-else class="size-4" />
                        Renvoyer l’e-mail
                    </Button>
                    <Button v-if="!is_guest" as-child variant="outline">
                        <Link :href="reservationsIndex()">
                            <Ticket class="size-4" />
                            Mes réservations
                        </Link>
                    </Button>
                </div>
            </SiteContainer>
        </main>
    </div>
</template>
