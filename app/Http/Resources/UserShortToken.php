<?php

namespace App\Http\Resources;

class UserShortToken extends AbstractResource
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
        $data['quick_token'] = $this->toQuickToken();
        unset($data['token']);

        return $data;
    }
}
