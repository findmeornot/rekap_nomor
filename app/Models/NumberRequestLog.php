<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number_request_id', 'actor_id', 'status', 'note'])]
class NumberRequestLog extends Model
{
    public $timestamps = false;

    public function request(): BelongsTo
    {
        return $this->belongsTo(NumberRequest::class, 'number_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
