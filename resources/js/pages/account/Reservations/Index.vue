<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as reservationShow } from '@/routes/account/reservations';
import { index as billetsIndex } from '@/routes/account/billets';

type ReservationRow = {
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
};

defineProps<{
    reservations: ReservationRow[];
}>();

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (status === 'confirmed') {
        return 'default';
    }

    if (status === 'pending') {
        return 'outline';
    }

    if (status === 'cancelled' || status === 'expired') {
        return 'destructive';
    }

    return 'secondary';
}
</script>

<template>
    <Head title="Mes réservations" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer>
                <div
                    class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h1 class="text-3xl font-semibold">Mes réservations</h1>
                    <Button as-child variant="outline">
                        <Link :href="billetsIndex()">Mes billets</Link>
                    </Button>
                </div>

                <ul class="space-y-4">
                    <li
                        v-for="reservation in reservations"
                        :key="reservation.id"
                        class="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-center"
                    >
                        <img
                            v-if="reservation.film_poster_url"
                            :src="reservation.film_poster_url"
                            :alt="reservation.film_title"
                            class="size-16 rounded-md object-cover"
                        />
                        <div class="flex-1">
                            <p class="font-medium">
                                {{ reservation.film_title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ reservation.seance_date_formatted }} —
                                {{ reservation.seance_time_range }} ·
                                {{ reservation.salle_name }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ reservation.reference }} ·
                                {{ reservation.quantity }} place(s) ·
                                {{ reservation.total_amount_formatted }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge :variant="statusVariant(reservation.status)">
                                {{ reservation.status_label }}
                            </Badge>
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="
                                        reservationShow({
                                            reservation: reservation.id,
                                        })
                                    "
                                >
                                    Détail
                                </Link>
                            </Button>
                        </div>
                    </li>
                </ul>

                <p
                    v-if="reservations.length === 0"
                    class="py-16 text-center text-muted-foreground"
                >
                    Aucune réservation pour le moment.
                </p>
            </SiteContainer>
        </main>
    </div>
</template>
