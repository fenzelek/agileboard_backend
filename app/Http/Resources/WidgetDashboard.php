<?php

namespace App\Http\Resources;

use App\Modules\Agile\Models\WidgetDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property WidgetDTO[] $resource
 */

class WidgetDashboard extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $data = [];
        foreach ($this->resource as $resource => $widgetDTO) {
            $data[$widgetDTO->getName()] = new $resource($widgetDTO->getData());
        }

        return $data;
    }
}
