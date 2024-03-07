<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Modules\Integration\Http\Requests\IntegrationCreateForProvider;
use Illuminate\Validation\Rule;

class Upwork extends IntegrationCreateForProvider
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        /* this validation is make to not ever pass - it should be replaced when Upwork service
        will be really added */
        $field = 'sample_' . microtime(true);

        return [
            $field => [
                'required',
                Rule::in(['TEST']),
            ],
        ];
    }
}
