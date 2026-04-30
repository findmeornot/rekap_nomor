<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['contact_name', 'phone', 'sub_leader_id', 'leader_id', 'contacted_at', 'contacted_by_leader_id'])]
class Contact extends Model
{
    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    public function getWhatsappPhoneAttribute(): string
    {
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

    public function subLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sub_leader_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
}
