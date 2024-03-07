<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Services\Contracts\Integration;

class InternalTimeTracker implements Integration
{
    public static function add(Company $company, IntegrationProvider $provider, array $settings)
    {
        return $company->integrations()->create([
            'integration_provider_id' => $provider->id,
            'settings' => [],
            'active' => true,
        ]);
    }

    public function getInfo()
    {
        return [];
    }

    public static function getValidationClass()
    {
        return \App\Modules\Integration\Http\Requests\TimeTracking\InternalTimeTracker::class;
    }
}
