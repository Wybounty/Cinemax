<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Dashboard/UserController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { index as usersIndex } from '@/routes/dashboard/users';

type RoleOption = {
    slug: string;
    name: string;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: RoleOption | null;
    created_at: string;
    created_at_formatted: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: ManagedUser[];
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
    role: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: Filters;
    filterRoles: RoleOption[];
    formRoles: RoleOption[];
    canManageAdminUsers: boolean;
    passwordRules: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Utilisateurs', href: usersIndex() },
        ],
    },
});

const page = usePage();
const search = ref(props.filters.search);
const roleFilter = ref(props.filters.role || 'all');
const createOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const selectedUser = ref<ManagedUser | null>(null);

const deleteError = computed(() => page.props.errors?.user as string | undefined);

const userRows = computed(() => props.users.data ?? []);

const pagination = computed(() => ({
    currentPage:
        props.users.meta?.current_page ?? props.users.current_page ?? 1,
    lastPage: props.users.meta?.last_page ?? props.users.last_page ?? 1,
    total: props.users.meta?.total ?? props.users.total ?? 0,
    links: props.users.links ?? [],
}));

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        roleFilter.value = filters.role || 'all';
    },
);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch([search, roleFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            usersIndex.url({
                query: {
                    search: search.value || undefined,
                    role:
                        roleFilter.value === 'all'
                            ? undefined
                            : roleFilter.value,
                    sort: props.filters.sort,
                    direction: props.filters.direction,
                },
            }),
            {},
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function sortBy(column: string): void {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get(
        usersIndex.url({
            query: {
                search: props.filters.search || undefined,
                role: props.filters.role || undefined,
                sort: column,
                direction,
            },
        }),
        {},
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function sortIndicator(column: string): string {
    if (props.filters.sort !== column) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}

function openEdit(user: ManagedUser): void {
    selectedUser.value = user;
    editOpen.value = true;
}

function openDelete(user: ManagedUser): void {
    selectedUser.value = user;
    deleteOpen.value = true;
}

function roleBadgeVariant(
    slug: string | undefined,
): 'default' | 'secondary' | 'outline' {
    if (slug === 'super_admin') {
        return 'default';
    }

    if (slug === 'admin') {
        return 'secondary';
    }

    return 'outline';
}

function roleLabel(slug: string | undefined): string {
    switch (slug) {
        case 'super_admin':
            return 'super admin';
        case 'admin':
            return 'admin';
        default:
            return 'spectateur';
    }
}

function canManageUser(user: ManagedUser): boolean {
    if (props.canManageAdminUsers) {
        return true;
    }

    return user.role?.slug === 'spectateur';
}

const editFormRoles = computed(() => {
    if (
        props.canManageAdminUsers &&
        selectedUser.value?.role?.slug === 'super_admin'
    ) {
        return [
            {
                slug: 'super_admin',
                name: 'Super administrateur',
            },
            ...props.formRoles,
        ];
    }

    return props.formRoles;
});
</script>

<template>
    <Head title="Gestion des utilisateurs" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Gestion des utilisateurs"
                description="Consultez, recherchez et gérez les comptes inscrits sur Grand Cinéma."
            />
            <Button type="button" class="shrink-0" @click="createOpen = true">
                <Plus class="size-4" />
                Ajouter un utilisateur
            </Button>
        </div>

        <AlertError
            v-if="deleteError"
            :errors="[deleteError]"
            title="Suppression impossible"
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
                    placeholder="Nom ou e-mail…"
                    autocomplete="off"
                />
            </div>
            <div class="grid w-full gap-2 md:w-52">
                <Label for="role-filter">Rôle</Label>
                <select
                    id="role-filter"
                    v-model="roleFilter"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30"
                >
                    <option value="all">Tous les rôles</option>
                    <option
                        v-for="role in filterRoles"
                        :key="role.slug"
                        :value="role.slug"
                    >
                        {{ role.name }}
                    </option>
                </select>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b bg-muted/40 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                <button
                                    type="button"
                                    class="hover:text-foreground"
                                    @click="sortBy('id')"
                                >
                                    ID{{ sortIndicator('id') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 font-medium">
                                <button
                                    type="button"
                                    class="hover:text-foreground"
                                    @click="sortBy('name')"
                                >
                                    Nom{{ sortIndicator('name') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 font-medium">
                                <button
                                    type="button"
                                    class="hover:text-foreground"
                                    @click="sortBy('email')"
                                >
                                    E-mail{{ sortIndicator('email') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 font-medium">
                                <button
                                    type="button"
                                    class="hover:text-foreground"
                                    @click="sortBy('role')"
                                >
                                    Rôle{{ sortIndicator('role') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 font-medium">
                                <button
                                    type="button"
                                    class="hover:text-foreground"
                                    @click="sortBy('created_at')"
                                >
                                    Inscription{{ sortIndicator('created_at') }}
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in userRows"
                            :key="user.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">{{ user.id }}</td>
                            <td class="px-4 py-3">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        roleBadgeVariant(user.role?.slug)
                                    "
                                >
                                    {{ roleLabel(user.role?.slug) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ user.created_at_formatted }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="canManageUser(user)"
                                    class="flex justify-end gap-1"
                                >
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        aria-label="Modifier"
                                        @click="openEdit(user)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Supprimer"
                                        @click="openDelete(user)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                                <span
                                    v-else
                                    class="block text-right text-xs text-muted-foreground"
                                >
                                    Réservé au super admin
                                </span>
                            </td>
                        </tr>
                        <tr v-if="userRows.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Aucun utilisateur trouvé.
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
                {{ pagination.lastPage }} — {{ pagination.total }} utilisateur(s)
            </p>
            <div class="flex flex-wrap gap-1">
                <template v-for="(link, index) in pagination.links" :key="index">
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
        <DialogContent class="sm:max-w-md">
            <Form
                v-bind="UserController.store.form()"
                :reset-on-success="['password', 'password_confirmation']"
                :options="{ preserveScroll: true }"
                @success="createOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Ajouter un utilisateur</DialogTitle>
                    <DialogDescription>
                        Créez un compte avec un rôle spectateur ou admin.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="create-name">Nom</Label>
                    <Input
                        id="create-name"
                        name="name"
                        required
                        autocomplete="name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-email">Adresse e-mail</Label>
                    <Input
                        id="create-email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-password">Mot de passe</Label>
                    <PasswordInput
                        id="create-password"
                        name="password"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="create-password-confirmation">
                        Confirmation du mot de passe
                    </Label>
                    <PasswordInput
                        id="create-password-confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="create-role">Rôle</Label>
                    <select
                        id="create-role"
                        name="role"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30"
                    >
                        <option
                            v-for="role in formRoles"
                            :key="role.slug"
                            :value="role.slug"
                        >
                            {{ role.name }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
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
        <DialogContent v-if="selectedUser" class="sm:max-w-md">
            <Form
                v-bind="
                    UserController.update.form({ user: selectedUser.id })
                "
                :options="{ preserveScroll: true }"
                @success="editOpen = false"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <DialogHeader>
                    <DialogTitle>Modifier l'utilisateur</DialogTitle>
                    <DialogDescription>
                        Mettez à jour les informations du compte. Le mot de passe
                        n'est pas modifié ici.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-name">Nom</Label>
                    <Input
                        id="edit-name"
                        name="name"
                        required
                        :default-value="selectedUser.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-email">Adresse e-mail</Label>
                    <Input
                        id="edit-email"
                        name="email"
                        type="email"
                        required
                        :default-value="selectedUser.email"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-role">Rôle</Label>
                    <select
                        id="edit-role"
                        name="role"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs dark:bg-input/30"
                        :default-value="selectedUser.role?.slug"
                    >
                        <option
                            v-for="role in editFormRoles"
                            :key="role.slug"
                            :value="role.slug"
                            :selected="role.slug === selectedUser.role?.slug"
                        >
                            {{ role.name }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
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

    <Dialog v-model:open="deleteOpen">
        <DialogContent v-if="selectedUser" class="sm:max-w-md">
            <Form
                v-bind="
                    UserController.destroy.form({ user: selectedUser.id })
                "
                :options="{ preserveScroll: true }"
                @success="deleteOpen = false"
                v-slot="{ processing }"
            >
                <DialogHeader>
                    <DialogTitle>Supprimer l'utilisateur</DialogTitle>
                    <DialogDescription>
                        Confirmez la suppression de
                        <strong>{{ selectedUser.name }}</strong>
                        ({{ selectedUser.email }}). Cette action est
                        irréversible si le compte n'a pas de réservations.
                    </DialogDescription>
                </DialogHeader>

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
