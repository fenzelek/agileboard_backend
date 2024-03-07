<?php

declare(strict_types=1);

namespace App\Models\Db;

use App\Interfaces\Interactions\IInteractionable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Involved extends Model implements IInteractionable
{
    protected $table = 'involved';
    protected $guarded = [];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'source');
    }
}
