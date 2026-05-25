<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'contact_name',
    'phone',
    'normalized_phone',
    'period_key',
    'team_id',
    'assistant_marketing_id',
    'input_by',
    'main_marketing_id',
    'status',
    'status_updated_by',
    'status_updated_at',
    'contacted_at',
    'contacted_by_main_marketing_id',
])]
class Contact extends Model
{
    public const STATUS_UNCONTACTED = 'belum_dihubungi';

    public const STATUS_CONTACTED = 'sudah_dihubungi';

    public static function activePeriodKey(): string
    {
        return now()->format('Y-m');
    }

    public static function statusFromInput(string $value): string
    {
        return $value === 'contacted'
            ? self::STATUS_CONTACTED
            : self::STATUS_UNCONTACTED;
    }

    public function isContacted(): bool
    {
        return $this->status === self::STATUS_CONTACTED;
    }

    public function statusLabel(): string
    {
        return $this->isContacted() ? 'Sudah Dihubungi' : 'Belum Dihubungi';
    }

    public function applyStatus(User $user, string $status): void
    {
        $this->update([
            'status' => $status,
            'status_updated_by' => $user->id,
            'status_updated_at' => now(),
        ]);
    }

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'status_updated_at' => 'datetime',
        ];
    }

    public function getWhatsappPhoneAttribute(): string
    {
        if ($this->normalized_phone) {
            return $this->normalized_phone;
        }

        $phone = preg_replace('/\D+/', '', (string) $this->phone) ?? '';

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        return $phone;
    }

    public function getWhatsappUrlAttribute(): string
    {
        return 'https://wa.me/'.$this->whatsapp_phone;
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function assistantMarketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_marketing_id');
    }

    public function subLeader(): BelongsTo
    {
        return $this->assistantMarketing();
    }

    public function mainMarketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_marketing_id');
    }

    public function leader(): BelongsTo
    {
        return $this->mainMarketing();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
