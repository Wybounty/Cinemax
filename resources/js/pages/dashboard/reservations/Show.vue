<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Ban, Ticket } from '@lucide/vue';
import { computed, ref } from 'vue';
import ReservationController from '@/actions/App/Http/Controllers/Dashboard/ReservationController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import {
    index as reservationsIndex,
    show as reservationsShow,
} from '@/routes/dashboard/reservations';

type PaiementRow = {
    id: number;
    amount_formatted: string;
    method_label: string;
    status: string;
    status_label: string;
    transaction_reference: string | null;
    paid_at_formatted: string | null;
};

type BilletRow = {
    id: number;
    ticket_code: string;
    status: string;
    status_label: string;
    issued_at_formatted: string;
};

type ReservationDetail = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    quantity: number;
    total_amount_formatted: string;
    reserved_at_formatted: string;
    expires_at: string | null;
    expires_at_formatted: string | null;
    can_cancel: boolean;
    client: {
        name: string;
        email: string;
        user_id: number | null;
        account_email: string | null;
    };
    film: { title: string; poster_url: string | null };
    seance: {
        date_formatted: string;
        time_range: string;
        salle_name: string;
        ticket_price_formatted: string;
    };
    payment: {
        status: string;
        status_label: string;
        amount_formatted: string;
        method_label: string;
        transaction_reference: string | null;
        paid_at_formatted: string | null;
    };
    paiements: PaiementRow[];
    billets: BilletRow[];
};

const props = defineProps<{
    reservation: ReservationDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            {
                title: 'Gestion des réservations',
                href: reservationsIndex(),
            },
            {
                title: props.reservation.reference,
                href: reservationsShow({ reservation: props.reservation.id }),
            },
        ],
    },
});

const page = usePage();
const cancelOpen = ref(false);

const cancelError = computed(
    () => page.props.errors?.reservation as string | undefined,
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

function billetBadgeVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'valid':
            return 'default';
        case 'used':
            return 'secondary';
        case 'cancelled':
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
                    :href="reservationsIndex()"
                    class="mb-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Retour à la liste
                </Link>
                <Heading
                    :title="`Réservation ${reservation.reference}`"
                    description="Détail complet, paiements simulés et billets émis."
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Badge :variant="reservationBadgeVariant(reservation.status)">
                    {{ reservation.status_label }}
                </Badge>
                <Badge
                    :variant="paymentBadgeVariant(reservation.payment.status)"
                >
                    {{ reservation.payment.status_label }}
                </Badge>
                <Button
                    v-if="reservation.can_cancel"
                    type="button"
                    variant="destructive"
                    class="gap-2"
                    @click="cancelOpen = true"
                >
                    <Ban class="size-4" />
                    Annuler
                </Button>
            </div>
        </div>

        <AlertError
            v-if="cancelError"
            :errors="[cancelError]"
            title="Annulation impossible"
        />

        <div class="grid gap-6 lg:grid-cols-2">
            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Client
                </h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Nom complet</dt>
                        <dd class="font-medium">{{ reservation.client.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">E-mail de contact</dt>
                        <dd>{{ reservation.client.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Compte (ID)</dt>
                        <dd class="font-mono text-xs">
                            {{
                                reservation.client.user_id
                                    ? `#${reservation.client.user_id} · ${reservation.client.account_email}`
                                    : '—'
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Film &amp; séance
                </h2>
                <div class="mt-4 flex gap-4">
                    <div
                        class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted"
                    >
                        <img
                            v-if="reservation.film.poster_url"
                            :src="reservation.film.poster_url"
                            :alt="reservation.film.title"
                            class="size-full object-cover"
                        />
                        <Ticket v-else class="size-6 text-muted-foreground" />
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Titre</dt>
                            <dd class="font-medium">
                                {{ reservation.film.title }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Date</dt>
                            <dd>{{ reservation.seance.date_formatted }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Horaire</dt>
                            <dd>{{ reservation.seance.time_range }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Salle</dt>
                            <dd>{{ reservation.seance.salle_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Prix unitaire</dt>
                            <dd>{{ reservation.seance.ticket_price_formatted }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Réservation
                </h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Référence</dt>
                        <dd class="font-mono text-xs font-medium">
                            {{ reservation.reference }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Places</dt>
                        <dd>{{ reservation.quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Montant total</dt>
                        <dd class="font-semibold">
                            {{ reservation.total_amount_formatted }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Créée le</dt>
                        <dd>{{ reservation.reserved_at_formatted }}</dd>
                    </div>
                    <div
                        v-if="
                            reservation.status === 'pending' &&
                            reservation.expires_at_formatted
                        "
                        class="sm:col-span-2"
                    >
                        <dt class="text-muted-foreground">Expiration</dt>
                        <dd>{{ reservation.expires_at_formatted }}</dd>
                    </div>
                </dl>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Paiement principal
                </h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Statut</dt>
                        <dd>
                            <Badge
                                :variant="
                                    paymentBadgeVariant(
                                        reservation.payment.status,
                                    )
                                "
                            >
                                {{ reservation.payment.status_label }}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Montant</dt>
                        <dd>{{ reservation.payment.amount_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Méthode</dt>
                        <dd>{{ reservation.payment.method_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Réf. transaction</dt>
                        <dd class="font-mono text-xs">
                            {{
                                reservation.payment.transaction_reference ?? '—'
                            }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-muted-foreground">Payé le</dt>
                        <dd>
                            {{
                                reservation.payment.paid_at_formatted ?? '—'
                            }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <section
            class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Historique des paiements
            </h2>
            <div
                v-if="reservation.paiements.length === 0"
                class="mt-4 text-sm text-muted-foreground"
            >
                Aucun enregistrement dans la table paiements.
            </div>
            <div v-else class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 font-medium">Montant</th>
                            <th class="px-3 py-2 font-medium">Méthode</th>
                            <th class="px-3 py-2 font-medium">Statut</th>
                            <th class="px-3 py-2 font-medium">Référence</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="paiement in reservation.paiements"
                            :key="paiement.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-2">
                                {{ paiement.amount_formatted }}
                            </td>
                            <td class="px-3 py-2">
                                {{ paiement.method_label }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    :variant="
                                        paymentBadgeVariant(paiement.status)
                                    "
                                >
                                    {{ paiement.status_label }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">
                                {{
                                    paiement.transaction_reference ?? '—'
                                }}
                            </td>
                            <td class="px-3 py-2 text-muted-foreground">
                                {{ paiement.paid_at_formatted ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="rounded-xl border border-sidebar-border/70 bg-card p-5 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                Billets générés
            </h2>
            <div
                v-if="reservation.billets.length === 0"
                class="mt-4 text-sm text-muted-foreground"
            >
                Aucun billet (réservation non payée ou en attente).
            </div>
            <div v-else class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="border-b text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 font-medium">Code</th>
                            <th class="px-3 py-2 font-medium">Statut</th>
                            <th class="px-3 py-2 font-medium">Émis le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="billet in reservation.billets"
                            :key="billet.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-2 font-mono text-xs">
                                {{ billet.ticket_code }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    :variant="
                                        billetBadgeVariant(billet.status)
                                    "
                                >
                                    {{ billet.status_label }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2 text-muted-foreground">
                                {{ billet.issued_at_formatted }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Dialog v-model:open="cancelOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Annuler cette réservation ?</DialogTitle>
                    <DialogDescription>
                        Les places seront libérées. Les billets valides seront
                        marqués comme annulés. Cette action ne supprime pas
                        l’historique.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    v-bind="
                        ReservationController.cancel.form({
                            reservation: reservation.id,
                        })
                    "
                    v-slot="{ processing }"
                    class="contents"
                    @success="cancelOpen = false"
                >
                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="cancelOpen = false"
                        >
                            Retour
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            class="gap-2"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            Confirmer l’annulation
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
