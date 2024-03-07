<?php

namespace App\Http\Resources;

use App\Models\Db\Ticket;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @property Ticket[] $resource
 */
class YourTasks extends ResourceCollection
{
    public $collects = YourTask::class;

}
