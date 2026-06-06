<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Team extends Model
{
    use HasFactory;

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function leaders(): HasMany
    {
        return $this->members()->where('role', User::ROLE_LEADER);
    }

    public function subLeaders(): HasMany
    {
        return $this->members()->where('role', User::ROLE_SUB_LEADER);
    }
}
