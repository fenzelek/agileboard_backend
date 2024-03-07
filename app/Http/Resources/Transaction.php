<?php

namespace App\Http\Resources;

class Transaction extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['transaction'];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->created_at) {
            $data['created_at'] = $this->created_at->toDateTimeString();
        }
        if ($this->updated_at) {
            $data['updated_at'] = $this->updated_at->toDateTimeString();
        }

        return $data;
    }
}
