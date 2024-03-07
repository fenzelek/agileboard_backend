<?php

namespace App\Modules\Integration\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class IntegrationCreate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'integration_provider_id' => [
                'required',
                'integer',
                Rule::exists('integration_providers', 'id'),
            ],
            'settings' => [
                'array',
            ],
        ];
    }
}
