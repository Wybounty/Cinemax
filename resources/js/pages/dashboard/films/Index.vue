<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Clapperboard,
    Eye,
    EyeOff,
    Pencil,
    Plus,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FilmController from '@/actions/App/Http/Controllers/Dashboard/FilmController';
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
import { index as filmsIndex } from '@/routes/dashboard/films';

type GenreOption = {
    id: number;
    name: string;
};

type ManagedFilm = {
    id: number;
    title: string;
    synopsis: string | null;
    poster_url: string | null;
    duration: number;
    duration_formatted: string;
    minimum_age: number | null;
    minimum_age_label: string;
    published: boolean;
    is_archived: boolean;
    genres: GenreOption[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedFilms = {
    data: ManagedFilm[];
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
    genre: string;
};

const props = defineProps<{
    films: PaginatedFilms;
    filters: Filters;
    filterGenres: GenreOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Gestion des films', href: filmsIndex() },
        ],
    },
});

const page = usePage();
const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status || 'all');
const genreFilter = ref(props.filters.genre || 'all');
const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const selectedFilm = ref<ManagedFilm | null>(null);
const createPosterPreview = ref<string | null>(null);
const editPosterPreview = ref<string | null>(null);

const archiveError = computed(() => page.props.errors?.film as string | undefined);

const filmRows = computed(() => props.films.data ?? []);

const pagination = computed(() => ({
    currentPage:
        props.films.meta?.current_page ?? props.films.current_page ?? 1,
    lastPage: props.films.meta?.last_page ?? props.films.last_page ?? 1,
    total: props.films.meta?.total ?? props.films.total ?? 0,
    links: props.films.links ?? [],
}));

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30';

const textareaClass =
    'flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs dark:bg-input/30';

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        statusFilter.value = filters.status || 'all';
        genreFilter.value = filters.genre || 'all';
    },
);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch([search, statusFilter, genreFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            filmsIndex.url({
                query: {
                    search: search.value || undefined,
                    status:
                        statusFilter.value === 'all'
                            ? undefined
                            : statusFilter.value,
                    genre:
                        genreFilter.value === 'all'
                            ? undefined
                            : genreFilter.value,
                },
            }),
            {},
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function openEdit(film: ManagedFilm): void {
    selectedFilm.value = film;
    editPosterPreview.value = null;
    editOpen.value = true;
}

function openArchive(film: ManagedFilm): void {
    selectedFilm.value = film;
    archiveOpen.value = true;
}

function genreIsSelected(film: ManagedFilm, genreId: number): boolean {
    return film.genres.some((genre) => genre.id === genreId);
}

function setFilmPublished(film: ManagedFilm, publish: boolean): void {
    const route = publish
        ? FilmController.publish({ film: film.id })
        : FilmController.unpublish({ film: film.id });

    router.visit(route.url, {
        method: route.method,
        preserveScroll: true,
    });
}

function statusVariant(
    film: ManagedFilm,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (film.is_archived) {
        return 'destructive';
    }

    return film.published ? 'default' : 'secondary';
}

function statusLabel(film: ManagedFilm): string {
    if (film.is_archived) {
        return 'Archivé';
    }

    return film.published ? 'Publié' : 'Non publié';
}

function onPosterChange(
    event: Event,
    target: 'create' | 'edit',
): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        if (typeof reader.result === 'string') {
            if (target === 'create') {
                createPosterPreview.value = reader.result;
            } else {
                editPosterPreview.value = reader.result;
            }
        }
    };
    reader.readAsDataURL(file);
}

watch(createOpen, (open) => {
    if (!open) {
        createPosterPreview.value = null;
    }
});

watch(editOpen, (open) => {
    if (!open) {
        editPosterPreview.value = null;
    }
});
</script>

<template>
    <Head title="Gestion des films" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Gestion des films"
                description="Ajoutez, modifiez, publiez et archivez le catalogue Grand Cinéma."
            />
            <Button type="button" class="shrink-0" @click="createOpen = true">
                <Plus class="size-4" />
                Ajouter un film
            </Button>
        </div>

        <AlertError
            v-if="archiveError"
            :errors="[archiveError]"
            title="Archivage impossible"
        />

        <div
            class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border md:flex-row md:items-end"
        >
            <div class="grid flex-1 gap-2">
                <Label for="search">Recherche</Label>
                <Input
                    id="search"
                    v-model="search"
                    type="search"
                    placeholder="Titre du film…"
                    autocomplete="off"
                />
            </div>
            <div class="grid w-full gap-2 md:w-52">
                <Label for="genre-filter">Genre</Label>
                <select
                    id="genre-filter"
                    v-model="genreFilter"
                    :class="selectClass"
                >
                    <option value="all">Tous les genres</option>
                    <option
                        v-for="genre in filterGenres"
                        :key="genre.id"
                        :value="String(genre.id)"
                    >
                        {{ genre.name }}
                    </option>
                </select>
            </div>
            <div class="grid w-full gap-2 md:w-52">
                <Label for="status-filter">Statut</Label>
                <select
                    id="status-filter"
                    v-model="statusFilter"
                    :class="selectClass"
                >
                    <option value="all">Tous (hors archivés)</option>
                    <option value="published">Publiés</option>
                    <option value="unpublished">Non publiés</option>
                    <option value="archived">Archivés</option>
                </select>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="max-h-[min(70vh,42rem)] overflow-auto">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead
                        class="sticky top-0 z-10 border-b bg-muted/95 text-muted-foreground shadow-sm backdrop-blur-sm [&_th]:bg-muted/95"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">Affiche</th>
                            <th class="px-4 py-3 font-medium">Titre</th>
                            <th class="px-4 py-3 font-medium">Durée</th>
                            <th class="px-4 py-3 font-medium">Âge min.</th>
                            <th class="px-4 py-3 font-medium">Genres</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="film in filmRows"
                            :key="film.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="flex size-14 items-center justify-center overflow-hidden rounded-md border bg-muted"
                                >
                                    <img
                                        v-if="film.poster_url"
                                        :src="film.poster_url"
                                        :alt="`Affiche de ${film.title}`"
                                        class="size-full object-cover"
                                    />
                                    <Clapperboard
                                        v-else
                                        class="size-6 text-muted-foreground"
                                    />
                                </div>
                            </td>
                            <td class="max-w-[200px] px-4 py-3 font-medium">
                                {{ film.title }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ film.duration_formatted }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ film.minimum_age_label }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="genre in film.genres"
                                        :key="genre.id"
                                        variant="outline"
                                    >
                                        {{ genre.name }}
                                    </Badge>
                                    <span
                                        v-if="film.genres.length === 0"
                                        class="text-muted-foreground"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(film)">
                                    {{ statusLabel(film) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="!film.is_archived"
                                    class="flex justify-end gap-1"
                                >
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Modifier"
                                        @click="openEdit(film)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="!film.published"
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Publier"
                                        @click="setFilmPublished(film, true)"
                                    >
                                        <Eye class="size-4" />
                                    </Button>
                                    <Button
                                        v-else
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Dépublier"
                                        @click="setFilmPublished(film, false)"
                                    >
                                        <EyeOff class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Archiver"
                                        @click="openArchive(film)"
                                    >
                                        <Archive class="size-4" />
                                    </Button>
                                </div>
                                <span
                                    v-else
                                    class="block text-right text-xs text-muted-foreground"
                                >
                                    Archivé
                                </span>
                            </td>
                        </tr>
                        <tr v-if="filmRows.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Aucun film trouvé.
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
                {{ pagination.lastPage }} — {{ pagination.total }} film(s)
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
                    <span
                        v-else
                        class="rounded-md border border-border px-3 py-1.5 opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <Dialog v-model:open="createOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <Form
                v-bind="FilmController.store.form()"
                :reset-on-success="['poster']"
                :options="{ preserveScroll: true }"
                @success="createOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
                enctype="multipart/form-data"
            >
                <DialogHeader>
                    <DialogTitle>Ajouter un film</DialogTitle>
                    <DialogDescription>
                        Renseignez les informations du film et associez un ou
                        plusieurs genres.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-title">Titre</Label>
                    <Input id="create-title" name="title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-synopsis">Synopsis</Label>
                    <textarea
                        id="create-synopsis"
                        name="synopsis"
                        :class="textareaClass"
                        rows="4"
                    />
                    <InputError :message="errors.synopsis" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="create-duration">Durée (minutes)</Label>
                        <Input
                            id="create-duration"
                            name="duration"
                            type="number"
                            min="1"
                            max="600"
                            required
                        />
                        <InputError :message="errors.duration" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-minimum-age">Âge minimum</Label>
                        <Input
                            id="create-minimum-age"
                            name="minimum_age"
                            type="number"
                            min="0"
                            max="99"
                            placeholder="Ex. 12"
                        />
                        <InputError :message="errors.minimum_age" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="create-published">Publication</Label>
                    <select
                        id="create-published"
                        name="published"
                        :class="selectClass"
                        default-value="0"
                    >
                        <option value="0">Non publié</option>
                        <option value="1">Publié</option>
                    </select>
                    <InputError :message="errors.published" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="text-sm font-medium">Genres</legend>
                    <div
                        class="grid gap-2 rounded-md border p-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="genre in filterGenres"
                            :key="genre.id"
                            class="flex cursor-pointer items-center gap-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                name="genre_ids[]"
                                :value="genre.id"
                                class="size-4 rounded border-input"
                            />
                            {{ genre.name }}
                        </label>
                    </div>
                    <InputError :message="errors.genre_ids" />
                </fieldset>

                <div class="grid gap-2">
                    <Label for="create-poster">Affiche</Label>
                    <Input
                        id="create-poster"
                        name="poster"
                        type="file"
                        accept="image/*"
                        @change="onPosterChange($event, 'create')"
                    />
                    <InputError :message="errors.poster" />
                    <img
                        v-if="createPosterPreview"
                        :src="createPosterPreview"
                        alt="Aperçu de l'affiche"
                        class="mt-2 max-h-48 rounded-md border object-contain"
                    />
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
                        Créer
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
        <DialogContent
            v-if="selectedFilm"
            class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
        >
            <Form
                v-bind="
                    FilmController.update.form({ film: selectedFilm.id })
                "
                :options="{ preserveScroll: true }"
                @success="editOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
                enctype="multipart/form-data"
            >
                <DialogHeader>
                    <DialogTitle>Modifier le film</DialogTitle>
                    <DialogDescription>
                        Mettez à jour les informations de
                        <strong>{{ selectedFilm.title }}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-title">Titre</Label>
                    <Input
                        id="edit-title"
                        name="title"
                        required
                        :default-value="selectedFilm.title"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-synopsis">Synopsis</Label>
                    <textarea
                        id="edit-synopsis"
                        name="synopsis"
                        :class="textareaClass"
                        rows="4"
                        :default-value="selectedFilm.synopsis ?? ''"
                    />
                    <InputError :message="errors.synopsis" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit-duration">Durée (minutes)</Label>
                        <Input
                            id="edit-duration"
                            name="duration"
                            type="number"
                            min="1"
                            max="600"
                            required
                            :default-value="String(selectedFilm.duration)"
                        />
                        <InputError :message="errors.duration" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-minimum-age">Âge minimum</Label>
                        <Input
                            id="edit-minimum-age"
                            name="minimum_age"
                            type="number"
                            min="0"
                            max="99"
                            :default-value="
                                selectedFilm.minimum_age !== null
                                    ? String(selectedFilm.minimum_age)
                                    : undefined
                            "
                        />
                        <InputError :message="errors.minimum_age" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="edit-published">Publication</Label>
                    <select
                        id="edit-published"
                        name="published"
                        :class="selectClass"
                    >
                        <option
                            value="0"
                            :selected="!selectedFilm.published"
                        >
                            Non publié
                        </option>
                        <option
                            value="1"
                            :selected="selectedFilm.published"
                        >
                            Publié
                        </option>
                    </select>
                    <InputError :message="errors.published" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="text-sm font-medium">Genres</legend>
                    <div
                        class="grid gap-2 rounded-md border p-3 sm:grid-cols-2"
                    >
                        <label
                            v-for="genre in filterGenres"
                            :key="genre.id"
                            class="flex cursor-pointer items-center gap-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                name="genre_ids[]"
                                :value="genre.id"
                                class="size-4 rounded border-input"
                                :checked="
                                    genreIsSelected(selectedFilm, genre.id)
                                "
                            />
                            {{ genre.name }}
                        </label>
                    </div>
                    <InputError :message="errors.genre_ids" />
                </fieldset>

                <div class="grid gap-2">
                    <Label for="edit-poster">Affiche</Label>
                    <Input
                        id="edit-poster"
                        name="poster"
                        type="file"
                        accept="image/*"
                        @change="onPosterChange($event, 'edit')"
                    />
                    <InputError :message="errors.poster" />
                    <img
                        v-if="editPosterPreview ?? selectedFilm.poster_url"
                        :src="editPosterPreview ?? selectedFilm.poster_url!"
                        alt="Aperçu de l'affiche"
                        class="mt-2 max-h-48 rounded-md border object-contain"
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="editOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Enregistrer
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="archiveOpen">
        <DialogContent v-if="selectedFilm" class="sm:max-w-md">
            <Form
                v-bind="
                    FilmController.destroy.form({ film: selectedFilm.id })
                "
                :options="{ preserveScroll: true }"
                @success="archiveOpen = false"
                v-slot="{ processing }"
            >
                <DialogHeader>
                    <DialogTitle>Archiver le film</DialogTitle>
                    <DialogDescription>
                        Confirmez l’archivage de
                        <strong>{{ selectedFilm.title }}</strong>. Le film sera
                        retiré du catalogue public mais conservé en base de
                        données. L’opération est bloquée s’il existe des séances
                        ou des réservations actives.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="archiveOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Archiver
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
