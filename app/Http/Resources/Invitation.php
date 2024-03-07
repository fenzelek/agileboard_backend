<?php

namespace App\Http\Resources;

class Invitation extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        unset($data['unique_hash']);
        $data['token'] = $this->unique_hash;

        return $data;
    }
}
