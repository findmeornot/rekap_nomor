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

#[Fillable(['name', 'email', 'password', 'role', 'leader_id', 'team_id', 'marketing_channel'])]
#[Hidden(['password', 'remember_token'])]
/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_LEADER     = 'leader';
    public const ROLE_SUB_LEADER = 'sub_leader';

    // Marketing channels
    public const MARKETING_CHANNEL_TOPLOKER   = 'toploker';
    public const MARKETING_CHANNEL_TOPMATCH   = 'topmatch';
    public const MARKETING_CHANNEL_KERJA_MALAM = 'kerja_malam';

    // Daily targets per role/channel
    public const TARGET_LEADER      = 300;
    public const TARGET_SUB_LEADER  = 150;
    public const TARGET_TOPMATCH    = 200;
    public const TARGET_KERJA_MALAM = 200;

    /**
     * Returns all valid marketing channel values.
     *
     * @return array<string>
     */
    public static function allMarketingChannels(): array
    {
        return [
            self::MARKETING_CHANNEL_TOPLOKER,
            self::MARKETING_CHANNEL_TOPMATCH,
            self::MARKETING_CHANNEL_KERJA_MALAM,
        ];
    }

    /**
     * Human-readable label for this user's marketing channel.
     */
    public function marketingChannelLabel(): string
    {
        return match ($this->marketing_channel) {
            self::MARKETING_CHANNEL_TOPMATCH   => 'Topmatch',
            self::MARKETING_CHANNEL_KERJA_MALAM => 'Kerja Malam',
            default                             => 'Toploker',
        };
    }

    /**
     * Daily target for this leader based on their marketing channel.
     */
    public function getDailyTarget(): int
    {
        return match ($this->marketing_channel) {
            self::MARKETING_CHANNEL_TOPMATCH   => self::TARGET_TOPMATCH,
            self::MARKETING_CHANNEL_KERJA_MALAM => self::TARGET_KERJA_MALAM,
            default                             => self::TARGET_LEADER,
        };
    }

    public function isToploker(): bool
    {
        $channel = $this->marketing_channel ?? self::MARKETING_CHANNEL_TOPLOKER;

        return $channel === self::MARKETING_CHANNEL_TOPLOKER;
    }

    public function isTopmatch(): bool
    {
        return $this->marketing_channel === self::MARKETING_CHANNEL_TOPMATCH;
    }

    public function isKerjaMalam(): bool
    {
        return $this->marketing_channel === self::MARKETING_CHANNEL_KERJA_MALAM;
    }

    /**
     * True for Topmatch and Kerja Malam leaders (special channels without sub-leaders).
     */
    public function isSpecialChannel(): bool
    {
        return $this->isTopmatch() || $this->isKerjaMalam();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function contactsEntered(): HasMany
    {
        return $this->hasMany(Contact::class, 'sub_leader_id');
    }

    public function contactsAsLeader(): HasMany
    {
        return $this->hasMany(Contact::class, 'leader_id');
    }

    public function contactsHandled(): HasMany
    {
        return $this->hasMany(Contact::class, 'contacted_by_leader_id');
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
