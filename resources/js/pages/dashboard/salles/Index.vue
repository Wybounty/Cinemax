<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Archive, Pencil, Plus, Power, PowerOff } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SalleController from '@/actions/App/Http/Controllers/Dashboard/SalleController';
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
import { index as sallesIndex } from '@/routes/dashboard/salles';

type ManagedSalle = {
    id: number;
    name: string;
    description: string | null;
    description_excerpt: string;
    capacity: number;
    status: string;
    is_archived: boolean;
};

type StatusOption = { value: string; label: string };

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedSalles = {
    data: ManagedSalle[];
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

type Filters = { search: string; status: string };

const props = defineProps<{
    salles: PaginatedSalles;
    filters: Filters;
    statusOptions: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Gestion des salles', href: sallesIndex() },
        ],
    },
});

const page = usePage();
const search = ref(props.filters.search);
const statusFilter = ref(props.filters.status || 'all');
const createOpen = ref(false);
const editOpen = ref(false);
const archiveOpen = ref(false);
const archiveForce = ref(false);
const selectedSalle = ref<ManagedSalle | null>(null);

const archiveError = computed(() => page.props.errors?.salle as string | undefined);
const needsArchiveConfirm = computed(
    () => page.props.errors?.requires_confirmation as string | undefined,
);

const salleRows = computed(() => props.salles.data ?? []);

const pagination = computed(() => ({
    currentPage:
        props.salles.meta?.current_page ?? props.salles.current_page ?? 1,
    lastPage: props.salles.meta?.last_page ?? props.salles.last_page ?? 1,
    total: props.salles.meta?.total ?? props.salles.total ?? 0,
    links: props.salles.links ?? [],
}));

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30';

const textareaClass =
    'flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs dark:bg-input/30';

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        statusFilter.value = filters.status || 'all';
    },
);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch([search, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            sallesIndex.url({
                query: {
                    search: search.value || undefined,
                    status:
                        statusFilter.value === 'all'
                            ? undefined
                            : statusFilter.value,
                },
            }),
            {},
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function openEdit(salle: ManagedSalle): void {
    selectedSalle.value = salle;
    editOpen.value = true;
}

function openArchive(salle: ManagedSalle): void {
    selectedSalle.value = salle;
    archiveForce.value = false;
    archiveOpen.value = true;
}

function setSalleStatus(salle: ManagedSalle, active: boolean): void {
    const route = active
        ? SalleController.activate({ salle: salle.id })
        : SalleController.deactivate({ salle: salle.id });

    router.visit(route.url, {
        method: route.method,
        preserveScroll: true,
    });
}

function statusVariant(
    salle: ManagedSalle,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (salle.is_archived) {
        return 'destructive';
    }

    return salle.status === 'active' ? 'default' : 'secondary';
}

function statusLabel(salle: ManagedSalle): string {
    if (salle.is_archived) {
        return 'Archivée';
    }

    return salle.status === 'active' ? 'Active' : 'Inactive';
}

watch(needsArchiveConfirm, (value) => {
    if (value && selectedSalle.value) {
        archiveForce.value = true;
        archiveOpen.value = true;
    }
});
</script>

<template>
    <Head title="Gestion des salles" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Gestion des salles"
                description="Gérez les salles, leur capacité et leur statut."
            />
            <Button type="button" class="shrink-0" @click="createOpen = true">
                <Plus class="size-4" />
                Ajouter une salle
            </Button>
        </div>

        <AlertError
            v-if="archiveError && !archiveOpen"
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
                    placeholder="Nom ou description…"
                    autocomplete="off"
                />
            </div>
            <div class="grid w-full gap-2 md:w-52">
                <Label for="status-filter">Statut</Label>
                <select
                    id="status-filter"
                    v-model="statusFilter"
                    :class="selectClass"
                >
                    <option value="all">Toutes (hors archivées)</option>
                    <option value="active">Actives</option>
                    <option value="inactive">Inactives</option>
                    <option value="archived">Archivées</option>
                </select>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="max-h-[min(70vh,42rem)] overflow-auto">
                <table class="w-full min-w-[880px] text-left text-sm">
                    <thead
                        class="sticky top-0 z-10 border-b bg-muted/95 text-muted-foreground shadow-sm backdrop-blur-sm [&_th]:bg-muted/95"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">Nom</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Capacité</th>
                            <th class="px-4 py-3 font-medium">Statut</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="salle in salleRows"
                            :key="salle.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ salle.name }}
                            </td>
                            <td class="max-w-xs px-4 py-3 text-muted-foreground">
                                {{ salle.description_excerpt }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ salle.capacity }} place(s)
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(salle)">
                                    {{ statusLabel(salle) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="!salle.is_archived"
                                    class="flex justify-end gap-1"
                                >
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Modifier"
                                        @click="openEdit(salle)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="salle.status !== 'active'"
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Activer"
                                        @click="setSalleStatus(salle, true)"
                                    >
                                        <Power class="size-4" />
                                    </Button>
                                    <Button
                                        v-else
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Désactiver"
                                        @click="setSalleStatus(salle, false)"
                                    >
                                        <PowerOff class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Archiver"
                                        @click="openArchive(salle)"
                                    >
                                        <Archive class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="salleRows.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Aucune salle trouvée.
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
                {{ pagination.lastPage }} — {{ pagination.total }} salle(s)
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
                v-bind="SalleController.store.form()"
                :options="{ preserveScroll: true }"
                @success="createOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Ajouter une salle</DialogTitle>
                    <DialogDescription>
                        Indiquez le nom, la description, la capacité et le
                        statut de la salle.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-name">Nom</Label>
                    <Input id="create-name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-description">Description</Label>
                    <textarea
                        id="create-description"
                        name="description"
                        :class="textareaClass"
                        rows="3"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-capacity">Capacité (places)</Label>
                    <Input
                        id="create-capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        max="65535"
                        required
                        default-value="100"
                    />
                    <InputError :message="errors.capacity" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-status">Statut</Label>
                    <select
                        id="create-status"
                        name="status"
                        :class="selectClass"
                        default-value="active"
                    >
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
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
        <DialogContent v-if="selectedSalle" class="sm:max-w-lg">
            <Form
                v-bind="
                    SalleController.update.form({ salle: selectedSalle.id })
                "
                :options="{ preserveScroll: true }"
                @success="editOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Modifier la salle</DialogTitle>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-name">Nom</Label>
                    <Input
                        id="edit-name"
                        name="name"
                        required
                        :default-value="selectedSalle.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-description">Description</Label>
                    <textarea
                        id="edit-description"
                        name="description"
                        :class="textareaClass"
                        rows="3"
                        :default-value="selectedSalle.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-capacity">Capacité (places)</Label>
                    <Input
                        id="edit-capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        max="65535"
                        required
                        :default-value="String(selectedSalle.capacity)"
                    />
                    <InputError :message="errors.capacity" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-status">Statut</Label>
                    <select
                        id="edit-status"
                        name="status"
                        :class="selectClass"
                        :default-value="selectedSalle.status"
                    >
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === selectedSalle.status"
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
        <DialogContent v-if="selectedSalle" class="sm:max-w-md">
            <Form
                v-bind="
                    SalleController.destroy.form({ salle: selectedSalle.id })
                "
                :options="{ preserveScroll: true }"
                @success="archiveOpen = false"
                v-slot="{ processing }"
            >
                <input
                    v-if="archiveForce"
                    type="hidden"
                    name="force"
                    value="1"
                />
                <DialogHeader>
                    <DialogTitle>Archiver la salle</DialogTitle>
                    <DialogDescription>
                        <template v-if="archiveForce">
                            Cette salle a des séances à venir. Confirmez
                            l’archivage de
                            <strong>{{ selectedSalle.name }}</strong>.
                        </template>
                        <template v-else>
                            Archiver
                            <strong>{{ selectedSalle.name }}</strong> ? Les
                            sièges seront conservés.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <AlertError
                    v-if="archiveError"
                    :errors="[archiveError]"
                    class="mt-2"
                />

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
