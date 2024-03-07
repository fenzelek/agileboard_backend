<?php

namespace App\Http\Resources;

class User extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'email',
        'first_name',
        'last_name',
        'avatar',
        'activated',
        'deleted',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['activated'] = (bool) $data['activated'];
        $data['deleted'] = (bool) $data['deleted'];

        // we use role only if we set system role - otherwise we don't return it
        if ($this->role) {
            $data['role'] = $this->role;
        }

        return $data;
    }
}
