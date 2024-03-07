<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionPing extends Model
{
    protected $guarded = [];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
