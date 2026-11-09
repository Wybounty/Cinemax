<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name'])]
class Role extends Model
{
    public const SLUG_SUPER_ADMIN = 'super_admin';

    public const SLUG_ADMIN = 'admin';

    public const SLUG_SPECTATEUR = 'spectateur';

    /**
     * @return list<string>
     */
    public static function privilegedSlugs(): array
    {
        return [self::SLUG_SUPER_ADMIN, self::SLUG_ADMIN];
    }

    /**
     * @return list<string>
     */
    public static function assignableFilterSlugs(): array
    {
        return [self::SLUG_SUPER_ADMIN, self::SLUG_ADMIN, self::SLUG_SPECTATEUR];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
