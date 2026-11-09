<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\DeleteManagedUser;
use App\Actions\Dashboard\UpdateManagedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUserRequest;
use App\Http\Requests\Dashboard\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Queries\ManagedUserIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, ManagedUserIndexQuery $query): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();

        /** @var User $actor */
        $actor = $request->user();

        $filterRoles = Role::query()
            ->orderBy('name')
            ->get(['slug', 'name']);

        return Inertia::render('dashboard/users/Index', [
            'users' => $query->paginate($search, $role, $sort, $direction),
            'filters' => [
                'search' => $search,
                'role' => in_array($role, Role::assignableFilterSlugs(), true) ? $role : '',
                'sort' => $query->normalizeSort($sort),
                'direction' => $query->normalizeDirection($direction),
            ],
            'filterRoles' => $filterRoles->map(fn (Role $roleModel): array => [
                'slug' => $roleModel->slug,
                'name' => $roleModel->name,
            ]),
            'formRoles' => $filterRoles
                ->whereIn('slug', $actor->assignableRoleSlugsForCreate())
                ->values()
                ->map(fn (Role $roleModel): array => [
                    'slug' => $roleModel->slug,
                    'name' => $roleModel->name,
                ]),
            'canManageAdminUsers' => $actor->canManageAdminUsers(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $role = Role::query()->where('slug', $request->validated('role'))->firstOrFail();

        User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role_id' => $role->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Utilisateur créé avec succès.'),
        ]);

        return redirect()->route('dashboard.users.index');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateManagedUser $updateManagedUser): RedirectResponse
    {
        $updateManagedUser($request->user(), $user, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Utilisateur mis à jour avec succès.'),
        ]);

        return redirect()->route('dashboard.users.index');
    }

    public function destroy(Request $request, User $user, DeleteManagedUser $deleteManagedUser ): RedirectResponse {
        $this->authorize('delete', $user);

        $outcome = $deleteManagedUser($request->user(), $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $outcome === DeleteManagedUser::OUTCOME_DEACTIVATED
                ? __('Compte désactivé : des réservations sont associées à cet utilisateur.')
                : __('Utilisateur supprimé avec succès.'),
        ]);

        return redirect()->route('dashboard.users.index');
    }
}
