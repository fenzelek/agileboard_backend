<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Modules\Integration\Http\Requests\IntegrationCreateForProvider;

class Hubstaff extends IntegrationCreateForProvider
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'settings.app_token' => [
                'required',
                'string',
            ],
            'settings.auth_token' => [
                'required',
                'string',
            ],
            'settings.start_time' => [
                'required',
                'string',
                'date_format:Y-m-d H:i:s',
            ],
        ];
    }
}
