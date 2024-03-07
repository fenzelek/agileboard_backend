<?php

namespace App\Http\Resources;

class ShortInvoice extends AbstractResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
        ];
    }
}
