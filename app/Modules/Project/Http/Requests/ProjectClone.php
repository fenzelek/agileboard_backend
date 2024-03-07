<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;

class ProjectClone extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|required',
            'short_name' => 'string|required',
        ];
    }
}
