<?php

namespace App\Modules\Integration\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class TimeSummary extends ResourceCollection
{
    public $collects = TimeSummaryItem::class;

}
