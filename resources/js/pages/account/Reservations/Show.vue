<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ReservationController from '@/actions/App/Http/Controllers/ReservationController';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { show as billetShow } from '@/routes/account/billets';
import { index as reservationsIndex } from '@/routes/account/reservations';

type ReservationDetail = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    film_title: string;
    film_poster_url: string | null;
    seance_date_formatted: string;
    seance_time_range: string;
    salle_name: string;
    quantity: number;
    total_amount_formatted: string;
    reserved_at_formatted: string;
    expires_at: string | null;
    can_pay: boolean;
    can_cancel: boolean;
    billets_count: number;
};

type BilletStub = { id: number; ticket_code: string; status: string };

const props = defineProps<{
    reservation: ReservationDetail;
    billets: BilletStub[];
}>();

const cancelling = ref(false);

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

const checkoutHref = computed(() =>
    ReservationController.checkout.url({ reservation: props.reservation.id }),
);
</script>

<template>
    <Head :title="`Réservation ${reservation.reference}`" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer class="max-w-3xl">
                <Link
                    :href="reservationsIndex()"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Retour à mes réservations
                </Link>

                <div class="mt-6 flex flex-wrap items-start gap-4">
                    <img
                        v-if="reservation.film_poster_url"
                        :src="reservation.film_poster_url"
                        class="size-24 rounded-lg object-cover"
                        :alt="reservation.film_title"
                    />
                    <div>
                        <h1 class="text-2xl font-semibold">
                            {{ reservation.film_title }}
                        </h1>
                        <Badge class="mt-2">{{ reservation.status_label }}</Badge>
                        <p class="mt-3 text-sm text-muted-foreground">
                            Réf. {{ reservation.reference }}
                        </p>
                    </div>
                </div>

                <dl class="mt-8 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Séance</dt>
                        <dd>
                            {{ reservation.seance_date_formatted }} —
                            {{ reservation.seance_time_range }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Salle</dt>
                        <dd>{{ reservation.salle_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Places</dt>
                        <dd>{{ reservation.quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Montant</dt>
                        <dd>{{ reservation.total_amount_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Réservé le</dt>
                        <dd>{{ reservation.reserved_at_formatted }}</dd>
                    </div>
                </dl>

                <div v-if="billets.length" class="mt-8">
                    <h2 class="text-lg font-medium">Billets</h2>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="billet in billets"
                            :key="billet.id"
                            class="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                        >
                            <span class="font-mono">{{ billet.ticket_code }}</span>
                            <Button as-child size="sm" variant="ghost">
                                <Link
                                    :href="
                                        billetShow({ billet: billet.id })
                                    "
                                >
                                    Voir
                                </Link>
                            </Button>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Button v-if="reservation.can_pay" as-child>
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
            </SiteContainer>
        </main>
    </div>
</template>
