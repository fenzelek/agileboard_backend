<?php

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Services\Contracts\Integration;
use App\Modules\Integration\Services\TimeTracking\Contracts\TimeTracking;
use App\Modules\Integration\Services\TimeTracking\InternalManualRecording;
use App\Modules\Integration\Services\TimeTracking\InternalTimeTracker;

class Factory
{
    /**
     * Create base integration handling class based on given provider.
     *
     * @param IntegrationProvider $provider
     *
     * @param array $settings
     * @param array $information
     *
     * @return TimeTracking|Integration
     * @throws \Exception
     */
    public static function make(IntegrationProvider $provider, $settings = [], $information = [])
    {
        switch ($provider->type) {
            case IntegrationProvider::TYPE_TIME_TRACKING:
                $class = (__NAMESPACE__ . '\\' . 'TimeTracking' . '\\' .
                    static::getNormalizedProviderSlug($provider->slug));

                return new $class($provider->slug, (array) $settings, (array) $information);
            case IntegrationProvider::TYPE_INTERNAL_TIME_TRACKING:
                return new InternalTimeTracker();

            case IntegrationProvider::TYPE_MANUAL_RECORDING:
                return new InternalManualRecording();
        }

        throw new \Exception('No class found');
    }

    /**
     * Get normalized provider slug.
     *
     * @param string $slug
     *
     * @return string
     */
    protected static function getNormalizedProviderSlug($slug)
    {
        return ucfirst(mb_strtolower($slug));
    }
}
