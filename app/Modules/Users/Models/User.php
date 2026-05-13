<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Wallets\Models\Wallet;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password',
        'role',
        'status',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function ownedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDriver(): bool
    {
        return $this->role === UserRole::Driver;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
