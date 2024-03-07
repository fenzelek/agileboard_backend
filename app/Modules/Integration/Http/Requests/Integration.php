<?php

namespace App\Modules\Integration\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class Integration extends Request
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'id' => [
                'nullable',
                'integer',
            ],
            'integration_provider_id' => [
                'nullable',
                'integer',
                Rule::exists('integration_providers', 'id'),
            ],
            'active' => [
                'nullable',
                'integer',
                Rule::in([0,1]),
            ],
        ];
    }
}
