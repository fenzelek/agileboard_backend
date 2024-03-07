<?php

namespace App\Models\Db\Integration;

use App\Models\Db\Company;
use App\Models\Db\Model;
use App\Modules\SaleInvoice\Traits\FindBySlug;

class IntegrationProvider extends Model
{
    use FindBySlug;

    const TYPE_TIME_TRACKING = 'time_tracking';
    const TYPE_INTERNAL_TIME_TRACKING = 'internal_time_tracking';
    const TYPE_MANUAL_RECORDING = 'internal_manual_recording';

    const HUBSTAFF = 'hubstaff';
    const UPWORK = 'upwork';
    const TIME_TRACKER = 'time-tracker';
    const MANUAL = 'manual';
    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Integration might be used my multiple companies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'integrations');
    }
}
