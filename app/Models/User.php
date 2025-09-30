<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\SafeEnumCast;
use App\Enums\UserRoleEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;


/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $activate
 * @property $role
 * @property int|null $default_app_id
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDefaultAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected ?Collection $myApps = null;
    protected ?App $defaultApp = null;
    protected bool $defaultAppLoaded = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => [SafeEnumCast::class, UserRoleEnum::class],
            'password' => 'hashed',
        ];
    }

    public function isAdmin() : bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }


    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activate;
    }


    public function getTenants(Panel $panel): array|Collection
    {
        if (is_null($this->myApps)) {
            $query = App::query()->withoutGlobalScopes();
            if (! $this->isAdmin()) {
                $query->where('owner_id', $this->getKey());
            }
            $this->myApps = $query->get();
        }

        return $this->myApps ?? [];
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->isAdmin() || $tenant->getAttribute('owner_id') === $this->getKey();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if (! $this->defaultAppLoaded) {
            dump($this->defaultAppLoaded);
            $this->defaultAppLoaded = true;
            if ($this->default_app_id) {
                $this->defaultApp = App::findOrNew($this->default_app_id);
            }
        }

        return $this->defaultApp;
    }
}
