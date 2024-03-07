<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Modules\Integration\Http\Requests\IntegrationCreateForProvider;

class InternalManualRecording extends IntegrationCreateForProvider
{
    public function rules()
    {
        return [
            'settings' => [],
        ];
    }
}
