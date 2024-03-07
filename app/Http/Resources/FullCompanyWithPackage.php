<?php

namespace App\Http\Resources;

class FullCompanyWithPackage extends FullCompany
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['full_vatin'] = $this->full_vatin;
        $package = $this->resource->realPackage();
        $date = $this->resource->realPackageExpiringDate();
        $data['real_package']['data'] = [
            'id' => $package ? $package->id : null,
            'slug' => $package ? $package->slug : null,
            'expires_at' => $date ? $date->toDateTimeString() : null,
        ];
        $data['vatin_prefix']['data'] = $this->vatinPrefix;

        return $data;
    }
}
