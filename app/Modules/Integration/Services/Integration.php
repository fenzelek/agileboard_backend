<?php

namespace App\Modules\Integration\Services;

use App\Models\Db\Company;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Services\Contracts\Integration as IntegrationContract;
use Carbon\Carbon;
use stdClass;

abstract class Integration implements IntegrationContract
{
    /**
     * Settings.
     *
     * @var stdClass
     */
    protected $settings;

    /**
     * Current date and time in UTC format.
     *
     * @var Carbon
     */
    protected $now;

    /**
     * Information that should be saved and will be reused in next run.
     *
     * @var array
     */
    protected $info = [];

    /**
     * Integration slug name.
     *
     * @var string
     */
    protected $slug;

    /**
     * Integration constructor.
     *
     * @param $slug
     * @param array $settings
     * @param array $info
     */
    public function __construct($slug, array $settings = [], array $info = [])
    {
        $this->settings = $settings;
        $this->info = $info;
        $this->now = Carbon::now('UTC');
        $this->slug = $slug;
    }

    /**
     * @inheritdoc
     */
    public static function add(Company $company, IntegrationProvider $provider, array $settings)
    {
        return $company->integrations()->create([
            'integration_provider_id' => $provider->id,
            'settings' => $settings,
            'active' => true,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getInfo()
    {
        return $this->info;
    }
}
