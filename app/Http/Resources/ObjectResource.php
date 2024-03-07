<?php

namespace App\Http\Resources;

use App\Models\Db\Model;

class ObjectResource extends AbstractResource
{
    /**
     * Transform object into array.
     *
     * @param Model $object
     *
     * @return array
     */
    public function toArray($request)
    {
        $data = $this->resource->toArray();

        $data = $this->transformRelations($data);

        return $data;
    }
}
