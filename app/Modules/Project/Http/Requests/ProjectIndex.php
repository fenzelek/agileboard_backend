<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ProjectIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => ['string', Rule::in(['closed', 'opened', 'all'])],
            'search' => ['string'],
            'has_access' => ['in:0,1'],
        ];
    }
}
