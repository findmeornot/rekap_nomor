<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['contact_name', 'phone', 'assistant_marketing_id', 'main_marketing_id', 'contacted_at', 'contacted_by_main_marketing_id'])]
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
}
