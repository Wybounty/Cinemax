<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property int|null $role_id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['role_id', 'name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === Role::SLUG_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === Role::SLUG_ADMIN;
    }

    public function canAccessUserManagement(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canManageAdminUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * @return list<string>
     */
    public function assignableRoleSlugsForCreate(): array
    {
        if ($this->isSuperAdmin()) {
            return [Role::SLUG_ADMIN, Role::SLUG_SPECTATEUR];
        }

        if ($this->isAdmin()) {
            return [Role::SLUG_SPECTATEUR];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    public function assignableRoleSlugsForUpdate(User $target): array
    {
        if ($this->isSuperAdmin()) {
            if ($target->isSuperAdmin()) {
                return [
                    Role::SLUG_SUPER_ADMIN,
                    Role::SLUG_ADMIN,
                    Role::SLUG_SPECTATEUR,
                ];
            }

            return [Role::SLUG_ADMIN, Role::SLUG_SPECTATEUR];
        }

        if ($this->isAdmin() && $target->role?->slug === Role::SLUG_SPECTATEUR) {
            return [Role::SLUG_SPECTATEUR];
        }

        return [];
    }

    public function hasPrivilegedRole(): bool
    {
        return in_array($this->role?->slug, Role::privilegedSlugs(), true);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function hasReservations(): bool
    {
        return $this->reservations()->exists();
    }

    public static function countActiveWithRoleSlug(string $slug): int
    {
        return static::query()
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $query) => $query->where('slug', $slug))
            ->count();
    }
}
