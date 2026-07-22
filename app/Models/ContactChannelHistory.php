<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contact_id',
    'marketing_channel',
    'marketing_user_id',
    'is_contacted',
    'contacted_at',
    'status_updated_by',
    'status_updated_at',
])]
class ContactChannelHistory extends Model
{
    protected function casts(): array
    {
        return [
            'contacted_at'     => 'datetime',
            'status_updated_at' => 'datetime',
            'is_contacted'     => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function marketingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_user_id');
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    /**
     * Create or update the channel history record for a contact.
     */
    public static function setContactedForChannel(
        Contact $contact,
        User $user,
        bool $isContacted,
        string $channel
    ): self {
        return static::updateOrCreate(
            [
                'contact_id'        => $contact->id,
                'marketing_channel' => $channel,
            ],
            [
                'marketing_user_id' => $user->id,
                'is_contacted'      => $isContacted,
                'contacted_at'      => $isContacted ? now() : null,
                'status_updated_by' => $user->id,
                'status_updated_at' => now(),
            ]
        );
    }

    /**
     * Bulk mark contacts as contacted for a specific channel.
     *
     * @param array<int> $contactIds
     */
    public static function bulkMarkContactedForChannel(
        array $contactIds,
        User $user,
        string $channel
    ): void {
        $now = now();

        foreach ($contactIds as $contactId) {
            static::updateOrCreate(
                [
                    'contact_id'        => $contactId,
                    'marketing_channel' => $channel,
                ],
                [
                    'marketing_user_id' => $user->id,
                    'is_contacted'      => true,
                    'contacted_at'      => $now,
                    'status_updated_by' => $user->id,
                    'status_updated_at' => $now,
                ]
            );
        }
    }
}
