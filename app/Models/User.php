<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'leader_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_LEADER = 'leader';
    public const ROLE_SUB_LEADER = 'sub_leader';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(self::class, 'leader_id');
    }

    public function subLeaders(): HasMany
    {
        return $this->hasMany(self::class, 'leader_id');
    }

    public function contactsEntered(): HasMany
    {
        return $this->hasMany(Contact::class, 'sub_leader_id');
    }

    public function contactsAsLeader(): HasMany
    {
        return $this->hasMany(Contact::class, 'leader_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isLeader(): bool
    {
        return $this->role === self::ROLE_LEADER;
    }

    public function isSubLeader(): bool
    {
        return $this->role === self::ROLE_SUB_LEADER;
    }
}
