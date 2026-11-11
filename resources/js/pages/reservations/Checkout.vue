<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CreditCard, Mail, Timer, User } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ReservationController from '@/actions/App/Http/Controllers/ReservationController';
import InputError from '@/components/InputError.vue';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type CheckoutReservation = {
    id: number;
    reference: string;
    status: string;
    quantity: number;
    total_amount_formatted: string;
    unit_price_formatted: string;
    expires_at: string | null;
    film: { title: string; poster_url: string | null };
    seance: { date_formatted: string; time_range: string };
    salle_name: string;
};

type ContactInfo = {
    name: string;
    email: string;
};

const props = defineProps<{
    reservation: CheckoutReservation;
    contact: ContactInfo;
    user_is_logged_in: boolean;
}>();

const contactName = ref(props.contact.name);
const contactEmail = ref(props.contact.email);

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

const expiresAtMs = computed(() =>
    props.reservation.expires_at
        ? new Date(props.reservation.expires_at).getTime()
        : null,
);

const secondsLeft = computed(() => {
    if (expiresAtMs.value === null) {
        return null;
    }

    return Math.max(0, Math.floor((expiresAtMs.value - now.value) / 1000));
});

const countdownLabel = computed(() => {
    if (secondsLeft.value === null) {
        return null;
    }

    const minutes = Math.floor(secondsLeft.value / 60);
    const seconds = secondsLeft.value % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const isExpired = computed(
    () => secondsLeft.value !== null && secondsLeft.value <= 0,
);

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<template>
    <Head title="Paiement simulé" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer class="max-w-3xl">
                <h1 class="text-3xl font-semibold tracking-tight">
                    Paiement simulé
                </h1>
                <p class="mt-2 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70">
                    Référence {{ reservation.reference }} — aucune carte
                    bancaire n’est demandée.
                </p>

                <div
                    v-if="countdownLabel"
                    class="mt-6 flex items-center gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm"
                >
                    <Timer class="size-4 shrink-0" />
                    <span v-if="!isExpired">
                        Temps restant pour régler :
                        <strong class="tabular-nums">{{ countdownLabel }}</strong>
                    </span>
                    <span v-else class="font-medium text-destructive">
                        Délai dépassé — ces places ne sont plus bloquées.
                    </span>
                </div>

                <div
                    class="mt-8 space-y-4 rounded-xl border border-[#19140035] bg-white p-6 shadow-sm dark:border-[#3E3E3A] dark:bg-[#111]"
                >
                    <div class="flex gap-4">
                        <img
                            v-if="reservation.film.poster_url"
                            :src="reservation.film.poster_url"
                            :alt="reservation.film.title"
                            class="size-24 rounded-lg object-cover"
                        />
                        <div>
                            <p class="text-lg font-medium">
                                {{ reservation.film.title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ reservation.seance.date_formatted }} —
                                {{ reservation.seance.time_range }}
                            </p>
                            <p class="text-sm">{{ reservation.salle_name }}</p>
                        </div>
                    </div>
                    <p class="text-sm">
                        {{ reservation.quantity }} place(s) ×
                        {{ reservation.unit_price_formatted }}
                    </p>
                    <p class="text-2xl font-semibold">
                        Total : {{ reservation.total_amount_formatted }}
                    </p>
                </div>

                <Form
                    v-bind="
                        ReservationController.pay.form({
                            reservation: reservation.id,
                        })
                    "
                    v-slot="{ errors, processing }"
                    class="mt-8 space-y-6"
                    :options="{ preserveScroll: true }"
                >
                    <div
                        class="rounded-xl border border-[#19140035] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#111]"
                    >
                        <h2 class="flex items-center gap-2 text-base font-medium">
                            <Mail class="size-4" />
                            Envoi des billets par e-mail (simulation)
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <template v-if="user_is_logged_in">
                                Vos coordonnées de compte sont préremplies.
                                Vous pouvez les modifier si besoin.
                            </template>
                            <template v-else>
                                Indiquez vos coordonnées pour recevoir le
                                message de démonstration.
                            </template>
                        </p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="contact_name">
                                    <User class="mr-1 inline size-3.5" />
                                    Nom
                                </Label>
                                <Input
                                    id="contact_name"
                                    name="contact_name"
                                    v-model="contactName"
                                    required
                                    autocomplete="name"
                                />
                                <InputError :message="errors.contact_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact_email">E-mail</Label>
                                <Input
                                    id="contact_email"
                                    name="contact_email"
                                    type="email"
                                    v-model="contactEmail"
                                    required
                                    autocomplete="email"
                                />
                                <InputError :message="errors.contact_email" />
                            </div>
                        </div>
                    </div>

                    <InputError :message="errors.reservation" />

                    <Button
                        type="submit"
                        class="w-full gap-2"
                        size="lg"
                        :disabled="processing || isExpired"
                    >
                        <Spinner v-if="processing" />
                        <CreditCard v-else class="size-4" />
                        Confirmer le paiement
                    </Button>
                </Form>
            </SiteContainer>
        </main>
    </div>
</template>
