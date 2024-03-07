<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Interaction extends Model
{
    protected $guarded = [];

    public function interactionPings(): HasMany
    {
        return $this->hasMany(InteractionPing::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
