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

#[Fillable(['name', 'email', 'password', 'role', 'main_marketing_id', 'team_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_LEADER = 'main_marketing';
    public const ROLE_SUB_LEADER = 'assistant_marketing';

    public const ROLE_MAIN_MARKETING = self::ROLE_LEADER;
    public const ROLE_ASSISTANT_MARKETING = self::ROLE_SUB_LEADER;

    public const TARGET_MAIN_MARKETING = 400;
    public const TARGET_ASSISTANT_MARKETING = 150;

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

    public function mainMarketing(): BelongsTo
    {
        return $this->belongsTo(self::class, 'main_marketing_id');
    }

    public function leader(): BelongsTo
    {
        return $this->mainMarketing();
    }

    public function assistantMarketings(): HasMany
    {
        return $this->hasMany(self::class, 'main_marketing_id');
    }

    public function subLeaders(): HasMany
    {
        return $this->assistantMarketings();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function contactsEntered(): HasMany
    {
        return $this->hasMany(Contact::class, 'assistant_marketing_id');
    }

    public function contactsAsLeader(): HasMany
    {
        return $this->hasMany(Contact::class, 'main_marketing_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isLeader(): bool
    {
        return $this->role === self::ROLE_MAIN_MARKETING;
    }

    public function isSubLeader(): bool
    {
        return $this->role === self::ROLE_ASSISTANT_MARKETING;
    }

    public function isMainMarketing(): bool
    {
        return $this->isLeader();
    }

    public function isAssistantMarketing(): bool
    {
        return $this->isSubLeader();
    }
}
