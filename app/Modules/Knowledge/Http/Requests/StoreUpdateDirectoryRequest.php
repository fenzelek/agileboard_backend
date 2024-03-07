<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\Knowledge\Traits\UserAndRolePermissions;

class StoreUpdateDirectoryRequest extends Request
{
    use UserAndRolePermissions;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ] + $this->getRules();
    }
}
