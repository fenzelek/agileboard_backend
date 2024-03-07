<?php

namespace App\Http\Resources;

use App\Models\Other\RoleType;

class CurrentUserCompanies extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'role',
        'vatin',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $user = auth()->user();

        $data['role'] = [
            'data' => $user->getCompanyRole($this->resource),
        ];

        $data['owner'] = [
            'data' => new User($this->resource->users()->whereHas('userCompanies', function ($query) {
                $query->whereHas('role', function ($query) {
                    $query->where('name', RoleType::OWNER);
                });
            })->first()),
        ];

        if (isset($data['owner']['data']->id) && $data['owner']['data']->id == $user->id) {
            $data['enabled'] = 1;
        } else {
            $data['enabled'] = (int) (null === $this->resource->blockade_company);
        }

        return $data;
    }
}
