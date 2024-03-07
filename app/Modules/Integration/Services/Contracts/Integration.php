<?php

namespace App\Modules\Integration\Services\Contracts;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;

interface Integration
{
    /**
     * Add new integration.
     *
     * @param Company $company
     * @param IntegrationProvider $provider
     * @param array $settings
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function add(Company $company, IntegrationProvider $provider, array $settings);

    /**
     * Get data that should be saved.
     *
     * @return array
     */
    public function getInfo();

    /**
     * Get validation class for current handler.
     *
     * @return string
     */
    public static function getValidationClass();
}
