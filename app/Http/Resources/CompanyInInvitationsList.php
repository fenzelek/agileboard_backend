<?php

namespace App\Http\Resources;

use App\Models\Other\RoleType;

class CompanyInInvitationsList extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'vatin',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['owner'] = [
            'data' => new User($this->resource->users()->whereHas('userCompanies', function ($query) {
                $query->whereHas('role', function ($query) {
                    $query->where('name', RoleType::OWNER);
                });
            })->first()),
        ];

        return $data;
    }
}
