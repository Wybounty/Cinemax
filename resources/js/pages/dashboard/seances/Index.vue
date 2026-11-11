<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarClock, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SeanceController from '@/actions/App/Http/Controllers/Dashboard/SeanceController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index as seancesIndex } from '@/routes/dashboard/seances';

type FilmOption = {
    id: number;
    title: string;
    duration: number;
};

type SalleOption = {
    id: number;
    name: string;
    status: string;
};

type StatusOption = { value: string; label: string };

type ManagedSeance = {
    id: number;
    film_id: number;
    film_title: string;
    film_poster_url: string | null;
    film_duration: number;
    salle_id: number;
    salle_name: string;
    salle_capacity: number;
    confirmed_seats: number;
    pending_seats: number;
    reserved_seats: number;
    remaining_seats: number;
    date: string;
    date_formatted: string;
    start_time: string;
    end_time: string;
    time_range: string;
    ticket_price: string;
    ticket_price_formatted: string;
    status: string;
    has_reservations: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedSeances = {
    data: ManagedSeance[];
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

type Filters = {
    search: string;
    status: string;
    salle: string;
    date: string;
    period: string;
};

const props = defineProps<{
    seances: PaginatedSeances;
    filters: Filters;
    filmOptions: FilmOption[];
    salleOptions: SalleOption[];
    statusOptions: StatusOption[];
    cleaningMinutes: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Gestion des séances', href: seancesIndex() },
        ],
    },
});

const page = usePage();
const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status || 'all');
const salleFilter = ref(props.filters.salle || 'all');
const dateFilter = ref(props.filters.date);
const periodFilter = ref(props.filters.period || 'all');

const createOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const selectedSeance = ref<ManagedSeance | null>(null);

const createFilmId = ref('');
const createDate = ref('');
const createStartTime = ref('');

const editFilmId = ref('');
const editDate = ref('');
const editStartTime = ref('');

const deleteError = computed(() => page.props.errors?.seance as string | undefined);

const seanceRows = computed(() => props.seances.data ?? []);

const activeSalleOptions = computed(() =>
    props.salleOptions.filter((salle) => salle.status === 'active'),
);

const pagination = computed(() => ({
    currentPage:
        props.seances.meta?.current_page ?? props.seances.current_page ?? 1,
    lastPage: props.seances.meta?.last_page ?? props.seances.last_page ?? 1,
    total: props.seances.meta?.total ?? props.seances.total ?? 0,
    links: props.seances.links ?? [],
}));

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30';

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        statusFilter.value = filters.status || 'all';
        salleFilter.value = filters.salle || 'all';
        dateFilter.value = filters.date;
        periodFilter.value = filters.period || 'all';
    },
);

let filterTimeout: ReturnType<typeof setTimeout> | undefined;

function applyFilters(): void {
    router.get(
        seancesIndex.url({
            query: {
                search: search.value || undefined,
                status:
                    statusFilter.value === 'all'
                        ? undefined
                        : statusFilter.value,
                salle:
                    salleFilter.value === 'all'
                        ? undefined
                        : salleFilter.value,
                date: dateFilter.value || undefined,
                period:
                    periodFilter.value === 'all'
                        ? undefined
                        : periodFilter.value,
            },
        }),
        {},
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watch([search, statusFilter, salleFilter, dateFilter], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 300);
});

function setPeriod(period: string): void {
    periodFilter.value = period;
    applyFilters();
}

function selectedFilmDuration(filmId: string): number {
    const id = Number(filmId);

    return props.filmOptions.find((film) => film.id === id)?.duration ?? 0;
}

function formatComputedEndTime(
    filmId: string,
    date: string,
    startTime: string,
): string | null {
    if (!filmId || !date || !startTime) {
        return null;
    }

    const duration = selectedFilmDuration(filmId);

    if (duration <= 0) {
        return null;
    }

    const [hours, minutes] = startTime.split(':').map(Number);
    const start = new Date(`${date}T${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`);
    const end = new Date(start.getTime() + duration * 60_000);

    return end.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const createEndTime = computed(() =>
    formatComputedEndTime(createFilmId.value, createDate.value, createStartTime.value),
);

const editEndTime = computed(() =>
    formatComputedEndTime(
        editFilmId.value || String(selectedSeance.value?.film_id ?? ''),
        editDate.value || selectedSeance.value?.date || '',
        editStartTime.value || selectedSeance.value?.start_time || '',
    ),
);

function openCreate(): void {
    createFilmId.value = '';
    createDate.value = '';
    createStartTime.value = '';
    createOpen.value = true;
}

function openEdit(seance: ManagedSeance): void {
    selectedSeance.value = seance;
    editFilmId.value = String(seance.film_id);
    editDate.value = seance.date;
    editStartTime.value = seance.start_time;
    editOpen.value = true;
}

function openDelete(seance: ManagedSeance): void {
    selectedSeance.value = seance;
    deleteOpen.value = true;
}

function statusVariant(
    seance: ManagedSeance,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (seance.status === 'cancelled') {
        return 'destructive';
    }

    if (seance.status === 'completed') {
        return 'secondary';
    }

    return 'default';
}

function statusLabel(seance: ManagedSeance): string {
    const match = props.statusOptions.find((option) => option.value === seance.status);

    return match?.label ?? seance.status;
}

function rowMuted(seance: ManagedSeance): boolean {
    return seance.status === 'cancelled' || seance.status === 'completed';
}
</script>

<template>
    <Head title="Gestion des séances" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Gestion des séances"
                description="Programmez les projections, gérez les créneaux et suivez le statut des séances."
            />
            <Button type="button" class="shrink-0" @click="openCreate">
                <Plus class="size-4" />
                Programmer une séance
            </Button>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                type="button"
                size="sm"
                :variant="periodFilter === 'today' ? 'default' : 'outline'"
                @click="setPeriod('today')"
            >
                <CalendarClock class="size-4" />
                Aujourd’hui
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="periodFilter === 'upcoming' ? 'default' : 'outline'"
                @click="setPeriod('upcoming')"
            >
                À venir
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="periodFilter === 'all' ? 'default' : 'outline'"
                @click="setPeriod('all')"
            >
                Toutes
            </Button>
        </div>

        <AlertError
            v-if="deleteError && !deleteOpen"
            :errors="[deleteError]"
            title="Suppression impossible"
        />

        <div
            class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border lg:grid lg:grid-cols-2 xl:grid-cols-4"
        >
            <div class="grid gap-2 xl:col-span-2">
                <Label for="search">Recherche (titre du film)</Label>
                <Input
                    id="search"
                    v-model="search"
                    type="search"
                    placeholder="Titre du film…"
                    autocomplete="off"
                />
            </div>
            <div class="grid gap-2">
                <Label for="salle-filter">Salle</Label>
                <select
                    id="salle-filter"
                    v-model="salleFilter"
                    :class="selectClass"
                >
                    <option value="all">Toutes les salles</option>
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
                <Label for="date-filter">Date</Label>
                <Input id="date-filter" v-model="dateFilter" type="date" />
            </div>
            <div class="grid gap-2">
                <Label for="status-filter">Statut</Label>
                <select
                    id="status-filter"
                    v-model="statusFilter"
                    :class="selectClass"
                >
                    <option value="all">Tous les statuts</option>
                    <option
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>

        <p class="text-xs text-muted-foreground">
            Délai de nettoyage entre deux séances dans la même salle :
            {{ cleaningMinutes }} minutes (appliqué côté serveur).
        </p>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="max-h-[min(70vh,42rem)] overflow-auto">
                <table class="w-full min-w-[1080px] text-left text-sm">
                    <thead
                        class="sticky top-0 z-10 border-b bg-muted/95 text-muted-foreground shadow-sm backdrop-blur-sm [&_th]:bg-muted/95"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">Film</th>
                            <th class="px-4 py-3 font-medium">Salle</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Horaire</th>
                            <th class="px-4 py-3 font-medium">Prix</th>
                            <th class="px-4 py-3 font-medium">Places</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="seance in seanceRows"
                            :key="seance.id"
                            class="border-b last:border-0"
                            :class="rowMuted(seance) ? 'opacity-70' : ''"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="seance.film_poster_url"
                                        :src="seance.film_poster_url"
                                        :alt="seance.film_title"
                                        class="size-12 rounded-md object-cover shadow-sm"
                                    />
                                    <div
                                        v-else
                                        class="flex size-12 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground"
                                    >
                                        N/A
                                    </div>
                                    <span class="font-medium">{{
                                        seance.film_title
                                    }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ seance.salle_name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ seance.date_formatted }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ seance.time_range }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ seance.ticket_price_formatted }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="seance.remaining_seats === 0"
                                    class="font-medium text-destructive"
                                >
                                    Complet
                                </span>
                                <span v-else>
                                    {{ seance.remaining_seats }} /
                                    {{ seance.salle_capacity }}
                                </span>
                                <span
                                    v-if="seance.reserved_seats > 0"
                                    class="mt-0.5 block text-xs text-muted-foreground"
                                >
                                    {{ seance.reserved_seats }} réservée(s)
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(seance)">
                                    {{ statusLabel(seance) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Modifier"
                                        @click="openEdit(seance)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="!seance.has_reservations"
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Supprimer"
                                        @click="openDelete(seance)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="seanceRows.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Aucune séance trouvée.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground"
        >
            <p>
                Page {{ pagination.currentPage }} sur
                {{ pagination.lastPage }} — {{ pagination.total }} séance(s)
            </p>
            <div class="flex flex-wrap gap-1">
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
            </div>
        </div>
    </div>

    <Dialog v-model:open="createOpen">
        <DialogContent class="sm:max-w-lg">
            <Form
                v-bind="SeanceController.store.form()"
                :options="{ preserveScroll: true }"
                @success="createOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Programmer une séance</DialogTitle>
                    <DialogDescription>
                        Sélectionnez le film et la salle. L’heure de fin est
                        calculée à partir de la durée du film.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-film">Film publié</Label>
                    <select
                        id="create-film"
                        name="film_id"
                        v-model="createFilmId"
                        :class="selectClass"
                        required
                    >
                        <option value="" disabled>Sélectionner un film</option>
                        <option
                            v-for="film in filmOptions"
                            :key="film.id"
                            :value="String(film.id)"
                        >
                            {{ film.title }} ({{ film.duration }} min)
                        </option>
                    </select>
                    <InputError :message="errors.film_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-salle">Salle active</Label>
                    <select
                        id="create-salle"
                        name="salle_id"
                        :class="selectClass"
                        required
                    >
                        <option value="" disabled>Sélectionner une salle</option>
                        <option
                            v-for="salle in activeSalleOptions"
                            :key="salle.id"
                            :value="salle.id"
                        >
                            {{ salle.name }}
                        </option>
                    </select>
                    <p
                        v-if="activeSalleOptions.length === 0"
                        class="text-sm text-destructive"
                    >
                        Aucune salle active disponible. Activez une salle depuis
                        la gestion des salles.
                    </p>
                    <InputError :message="errors.salle_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="create-date">Date</Label>
                        <Input
                            id="create-date"
                            name="date"
                            type="date"
                            v-model="createDate"
                            required
                        />
                        <InputError :message="errors.date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-start">Heure de début</Label>
                        <Input
                            id="create-start"
                            name="start_time"
                            type="time"
                            v-model="createStartTime"
                            required
                        />
                        <InputError :message="errors.start_time" />
                    </div>
                </div>

                <div
                    v-if="createEndTime"
                    class="rounded-md border border-dashed border-primary/40 bg-primary/5 px-3 py-2 text-sm"
                >
                    Heure de fin estimée :
                    <strong>{{ createEndTime }}</strong>
                    <span
                        v-if="createFilmId"
                        class="text-muted-foreground"
                    >
                        (durée : {{ selectedFilmDuration(createFilmId) }} min)
                    </span>
                </div>

                <div class="grid gap-2">
                    <Label for="create-price">Prix du billet (€)</Label>
                    <Input
                        id="create-price"
                        name="ticket_price"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        default-value="10.00"
                    />
                    <InputError :message="errors.ticket_price" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="createOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Programmer
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
        <DialogContent v-if="selectedSeance" class="sm:max-w-lg">
            <Form
                v-bind="
                    SeanceController.update.form({
                        seance: selectedSeance.id,
                    })
                "
                :options="{ preserveScroll: true }"
                @success="editOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Modifier la séance</DialogTitle>
                    <DialogDescription>
                        Modifiez le créneau ou annulez la séance via le statut.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-film">Film</Label>
                    <select
                        id="edit-film"
                        name="film_id"
                        v-model="editFilmId"
                        :class="selectClass"
                        required
                    >
                        <option
                            v-for="film in filmOptions"
                            :key="film.id"
                            :value="String(film.id)"
                            :selected="film.id === selectedSeance.film_id"
                        >
                            {{ film.title }} ({{ film.duration }} min)
                        </option>
                    </select>
                    <InputError :message="errors.film_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-salle">Salle</Label>
                    <select
                        id="edit-salle"
                        name="salle_id"
                        :class="selectClass"
                        required
                        :default-value="String(selectedSeance.salle_id)"
                    >
                        <option
                            v-for="salle in salleOptions"
                            :key="salle.id"
                            :value="salle.id"
                            :selected="salle.id === selectedSeance.salle_id"
                        >
                            {{ salle.name }}
                        </option>
                    </select>
                    <InputError :message="errors.salle_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit-date">Date</Label>
                        <Input
                            id="edit-date"
                            name="date"
                            type="date"
                            v-model="editDate"
                            required
                        />
                        <InputError :message="errors.date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-start">Heure de début</Label>
                        <Input
                            id="edit-start"
                            name="start_time"
                            type="time"
                            v-model="editStartTime"
                            required
                        />
                        <InputError :message="errors.start_time" />
                    </div>
                </div>

                <div
                    v-if="editEndTime"
                    class="rounded-md border border-dashed border-primary/40 bg-primary/5 px-3 py-2 text-sm"
                >
                    Heure de fin estimée :
                    <strong>{{ editEndTime }}</strong>
                </div>

                <div class="grid gap-2">
                    <Label for="edit-price">Prix du billet (€)</Label>
                    <Input
                        id="edit-price"
                        name="ticket_price"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        :default-value="selectedSeance.ticket_price"
                    />
                    <InputError :message="errors.ticket_price" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-status">Statut</Label>
                    <select
                        id="edit-status"
                        name="status"
                        :class="selectClass"
                        :default-value="selectedSeance.status"
                    >
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === selectedSeance.status"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.status" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="editOpen = false"
                    >
                        Fermer
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Enregistrer
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteOpen">
        <DialogContent v-if="selectedSeance" class="sm:max-w-md">
            <Form
                v-bind="
                    SeanceController.destroy.form({
                        seance: selectedSeance.id,
                    })
                "
                :options="{ preserveScroll: true }"
                @success="deleteOpen = false"
                v-slot="{ processing }"
            >
                <DialogHeader>
                    <DialogTitle>Supprimer la séance</DialogTitle>
                    <DialogDescription>
                        Supprimer définitivement la séance de
                        <strong>{{ selectedSeance.film_title }}</strong> du
                        {{ selectedSeance.date_formatted }} ?
                    </DialogDescription>
                </DialogHeader>

                <AlertError
                    v-if="deleteError"
                    :errors="[deleteError]"
                    class="mt-2"
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="deleteOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Supprimer
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
