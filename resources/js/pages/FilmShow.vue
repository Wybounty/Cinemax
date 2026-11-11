<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Clock,
    Clapperboard,
    MapPin,
    Minus,
    Plus,
    Ticket,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { film as filmsIndex } from '@/routes';

type Genre = {
    id: number;
    name: string;
};

type CatalogFilmDetail = {
    id: number;
    title: string;
    synopsis: string | null;
    poster_url: string | null;
    duration: number;
    duration_formatted: string;
    minimum_age: number | null;
    minimum_age_label: string;
    genres: Genre[];
};

type CatalogSeance = {
    id: number;
    date: string;
    date_formatted: string;
    date_short: string;
    start_time: string;
    end_time: string;
    time_range: string;
    salle_name: string;
    salle_capacity: number;
    reserved_seats: number;
    remaining_seats: number;
    is_sold_out: boolean;
    ticket_price: string;
    ticket_price_formatted: string;
    status: string;
    status_label: string;
};

const props = defineProps<{
    film: CatalogFilmDetail;
    seances: CatalogSeance[];
}>();

const selectedSeanceId = ref<number | null>(
    props.seances.find((seance) => !seance.is_sold_out)?.id ??
        props.seances[0]?.id ??
        null,
);
const quantity = ref(1);
const reservationNotice = ref<string | null>(null);

const seancesByDate = computed(() => {
    const groups = new Map<string, CatalogSeance[]>();

    for (const seance of props.seances) {
        const items = groups.get(seance.date) ?? [];
        items.push(seance);
        groups.set(seance.date, items);
    }

    return [...groups.entries()].map(([date, items]) => ({
        date,
        label: items[0]?.date_formatted ?? date,
        seances: items,
    }));
});

const selectedSeance = computed(
    () => props.seances.find((s) => s.id === selectedSeanceId.value) ?? null,
);

const unitPrice = computed(() => {
    if (!selectedSeance.value) {
        return 0;
    }

    return Number.parseFloat(selectedSeance.value.ticket_price);
});

const totalPrice = computed(() =>
    (unitPrice.value * quantity.value).toLocaleString('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }),
);

const maxSelectableQuantity = computed(
    () => selectedSeance.value?.remaining_seats ?? 0,
);

function selectSeance(seance: CatalogSeance): void {
    selectedSeanceId.value = seance.id;
    quantity.value = seance.remaining_seats > 0 ? 1 : 0;
    reservationNotice.value = null;
}

function decrementQuantity(): void {
    if (quantity.value > 1) {
        quantity.value -= 1;
    }
}

function incrementQuantity(): void {
    if (quantity.value < maxSelectableQuantity.value) {
        quantity.value += 1;
    }
}

function onReserve(): void {
    reservationNotice.value =
        'La réservation en ligne arrive bientôt. Merci de votre patience !';
}
</script>

<template>
    <Head :title="film.title" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer class="max-w-6xl">
                <div class="mb-8">
                    <Button
                        as-child
                        variant="ghost"
                        size="sm"
                        class="gap-2 text-[#1b1b18]/70 hover:text-[#1b1b18] dark:text-[#EDEDEC]/70 dark:hover:text-[#EDEDEC]"
                    >
                        <Link :href="filmsIndex()">
                            <ArrowLeft class="size-4" />
                            Retour au catalogue
                        </Link>
                    </Button>
                </div>

                <section
                    class="grid gap-10 lg:grid-cols-[minmax(0,320px)_1fr] lg:gap-12"
                >
                    <div
                        class="relative mx-auto aspect-[2/3] w-full max-w-sm overflow-hidden rounded-2xl border border-[#19140035] bg-[#19140008] shadow-lg dark:border-[#3E3E3A] dark:bg-[#141414] lg:mx-0"
                    >
                        <img
                            v-if="film.poster_url"
                            :src="film.poster_url"
                            :alt="`Affiche de ${film.title}`"
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center"
                        >
                            <Clapperboard
                                class="size-16 text-[#1b1b18]/30 dark:text-[#EDEDEC]/30"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <header>
                            <h1
                                class="text-3xl font-semibold tracking-tight md:text-4xl"
                            >
                                {{ film.title }}
                            </h1>
                            <div
                                class="mt-4 flex flex-wrap gap-2 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70"
                            >
                                <span>{{ film.duration_formatted }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ film.minimum_age_label }}</span>
                            </div>
                            <ul
                                v-if="film.genres.length > 0"
                                class="mt-4 flex flex-wrap gap-2"
                            >
                                <li v-for="genre in film.genres" :key="genre.id">
                                    <Badge variant="secondary">{{
                                        genre.name
                                    }}</Badge>
                                </li>
                            </ul>
                        </header>

                        <p
                            v-if="film.synopsis"
                            class="max-w-3xl text-base leading-relaxed text-[#1b1b18]/80 dark:text-[#EDEDEC]/80"
                        >
                            {{ film.synopsis }}
                        </p>
                    </div>
                </section>

                <section class="mt-14">
                    <h2
                        class="text-2xl font-semibold tracking-tight md:text-3xl"
                    >
                        Séances à venir
                    </h2>
                    <p
                        class="mt-2 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70"
                    >
                        Choisissez un horaire pour consulter les informations de
                        réservation.
                    </p>

                    <p
                        v-if="seances.length === 0"
                        class="mt-8 rounded-xl border border-dashed border-[#19140035] bg-[#19140008] px-6 py-10 text-center text-[#1b1b18]/70 dark:border-[#3E3E3A] dark:bg-[#141414] dark:text-[#EDEDEC]/70"
                    >
                        Aucune séance à venir pour ce film pour le moment.
                        Revenez bientôt ou explorez d’autres titres à l’affiche.
                    </p>

                    <div v-else class="mt-8 space-y-8">
                        <div
                            v-for="group in seancesByDate"
                            :key="group.date"
                            class="rounded-xl border border-[#19140035] bg-white p-4 shadow-sm dark:border-[#3E3E3A] dark:bg-[#111111]"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-lg font-medium capitalize"
                            >
                                <Calendar class="size-5 opacity-70" />
                                {{ group.label }}
                            </h3>
                            <ul class="grid gap-3 sm:grid-cols-2">
                                <li
                                    v-for="seance in group.seances"
                                    :key="seance.id"
                                >
                                    <button
                                        type="button"
                                        class="w-full rounded-lg border px-4 py-3 text-left transition-colors"
                                        :class="
                                            selectedSeanceId === seance.id
                                                ? 'border-[#1b1b18] bg-[#19140008] dark:border-[#EDEDEC] dark:bg-[#1a1a1a]'
                                                : 'border-[#19140035] hover:border-[#19140060] hover:bg-[#19140006] dark:border-[#3E3E3A] dark:hover:border-[#5a5a56]'
                                        "
                                        @click="selectSeance(seance)"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <span
                                                class="flex items-center gap-2 text-base font-medium"
                                            >
                                                <Clock
                                                    class="size-4 shrink-0 opacity-70"
                                                />
                                                {{ seance.time_range }}
                                            </span>
                                            <Badge
                                                v-if="seance.is_sold_out"
                                                variant="destructive"
                                            >
                                                Complet
                                            </Badge>
                                            <Badge v-else variant="outline">{{
                                                seance.status_label
                                            }}</Badge>
                                        </div>
                                        <p
                                            class="mt-2 flex items-center gap-2 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70"
                                        >
                                            <MapPin class="size-4 shrink-0" />
                                            {{ seance.salle_name }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm"
                                            :class="
                                                seance.is_sold_out
                                                    ? 'font-medium text-red-600 dark:text-red-400'
                                                    : 'text-[#1b1b18]/80 dark:text-[#EDEDEC]/80'
                                            "
                                        >
                                            <template v-if="seance.is_sold_out">
                                                Aucune place disponible
                                            </template>
                                            <template v-else>
                                                {{ seance.remaining_seats }}
                                                place(s) restante(s) sur
                                                {{ seance.salle_capacity }}
                                            </template>
                                        </p>
                                        <p
                                            class="mt-1 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]"
                                        >
                                            {{ seance.ticket_price_formatted }}
                                        </p>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section
                    v-if="selectedSeance"
                    class="mt-12 rounded-2xl border border-[#19140035] bg-linear-to-br from-white to-[#f7f6f2] p-6 shadow-md dark:border-[#3E3E3A] dark:from-[#141414] dark:to-[#0f0f0f] md:p-8"
                >
                    <h2 class="text-xl font-semibold">Réservation</h2>
                    <p class="mt-1 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70">
                        Interface de démonstration — aucune réservation n’est
                        enregistrée pour le moment.
                    </p>

                    <div
                        class="mt-6 grid gap-8 lg:grid-cols-2 lg:items-start"
                    >
                        <div class="space-y-4 text-sm">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-[#1b1b18]/50 dark:text-[#EDEDEC]/50"
                                >
                                    Film
                                </p>
                                <p class="mt-1 text-base font-medium">
                                    {{ film.title }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-[#1b1b18]/50 dark:text-[#EDEDEC]/50"
                                >
                                    Date &amp; horaire
                                </p>
                                <p class="mt-1">
                                    {{ selectedSeance.date_formatted }} —
                                    {{ selectedSeance.time_range }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-[#1b1b18]/50 dark:text-[#EDEDEC]/50"
                                >
                                    Salle
                                </p>
                                <p class="mt-1">
                                    {{ selectedSeance.salle_name }}
                                </p>
                                <p
                                    class="text-[#1b1b18]/70 dark:text-[#EDEDEC]/70"
                                >
                                    Capacité totale :
                                    {{ selectedSeance.salle_capacity }} places
                                </p>
                                <p
                                    class="font-medium"
                                    :class="
                                        selectedSeance.is_sold_out
                                            ? 'text-red-600 dark:text-red-400'
                                            : 'text-emerald-700 dark:text-emerald-400'
                                    "
                                >
                                    <template v-if="selectedSeance.is_sold_out">
                                        Complet — 0 place restante
                                    </template>
                                    <template v-else>
                                        {{ selectedSeance.remaining_seats }}
                                        place(s) restante(s)
                                        <span
                                            v-if="selectedSeance.reserved_seats > 0"
                                            class="font-normal text-[#1b1b18]/60 dark:text-[#EDEDEC]/60"
                                        >
                                            ({{ selectedSeance.reserved_seats }}
                                            déjà réservée(s))
                                        </span>
                                    </template>
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-[#1b1b18]/50 dark:text-[#EDEDEC]/50"
                                >
                                    Prix unitaire
                                </p>
                                <p class="mt-1 font-medium">
                                    {{ selectedSeance.ticket_price_formatted }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-[#19140035] bg-white p-5 dark:border-[#3E3E3A] dark:bg-[#0a0a0a]"
                        >
                            <p class="text-sm font-medium">Nombre de places</p>
                            <div class="mt-3 flex items-center gap-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label="Retirer une place"
                                    :disabled="quantity <= 1"
                                    @click="decrementQuantity"
                                >
                                    <Minus class="size-4" />
                                </Button>
                                <span
                                    class="min-w-10 text-center text-lg font-semibold tabular-nums"
                                >
                                    {{ quantity }}
                                </span>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    aria-label="Ajouter une place"
                                    :disabled="
                                        quantity >= maxSelectableQuantity
                                    "
                                    @click="incrementQuantity"
                                >
                                    <Plus class="size-4" />
                                </Button>
                            </div>
                            <p
                                class="mt-6 text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70"
                            >
                                Total estimé
                            </p>
                            <p class="text-2xl font-semibold tracking-tight">
                                {{ totalPrice }}
                            </p>
                            <Button
                                type="button"
                                class="mt-6 w-full gap-2"
                                :disabled="
                                    selectedSeance.is_sold_out ||
                                    maxSelectableQuantity === 0
                                "
                                @click="onReserve"
                            >
                                <Ticket class="size-4" />
                                {{
                                    selectedSeance.is_sold_out
                                        ? 'Séance complète'
                                        : 'Réserver'
                                }}
                            </Button>
                            <p
                                v-if="reservationNotice"
                                class="mt-4 rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-900 dark:text-amber-100"
                                role="status"
                            >
                                {{ reservationNotice }}
                            </p>
                        </div>
                    </div>
                </section>
            </SiteContainer>
        </main>
    </div>
</template>
