<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @property \App\Models\Db\Ticket[] $resource
 */
class LastAddedList extends ResourceCollection
{
    public $collects = LastAddedItem::class;

}
