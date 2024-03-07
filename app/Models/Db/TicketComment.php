<?php

namespace App\Models\Db;

use App\Interfaces\Interactions\IInteractionable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property ?int $ticket_id
 * @property ?Ticket $ticket
 */
class TicketComment extends Model implements IInteractionable
{
    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'source');
    }
}
