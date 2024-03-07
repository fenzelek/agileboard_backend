<?php

namespace App\Models\Db\Integration;

use App\Models\Db\Company;
use App\Models\Db\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;
use Illuminate\Support\Str;

class Integration extends Model
{
    use Filterable;

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $hidden = ['settings', 'info'];

    /**
     * @inheritdoc
     */
    protected $casts = [
        'active' => 'boolean',
        'info' => 'array',
        // don't add settings column in here - it's handled via accessor and mutator in custom way
    ];

    /**
     * It belongs to company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * It belongs to integration provider.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }

    /**
     * Set settings attribute.
     *
     * @param array $value
     */
    public function setSettingsAttribute($value)
    {
        if ($value === null) {
            $this->attributes['settings'] = $value;

            return;
        }
        array_walk_recursive($value, function (&$setting, $key) {
            if (Str::contains($key, ['password', 'token'])) {
                $setting = encrypt($setting);
            }
        });
        $this->attributes['settings'] = json_encode($value);
    }

    /**
     * Get settings attribute.
     *
     * @param string $value
     *
     * @return array
     */
    public function getSettingsAttribute($value)
    {
        if ($value === null) {
            return $value;
        }

        $value = json_decode($value);
        array_walk_recursive($value, function (&$setting, $key) {
            if ($this->isSecretKey($key)) {
                $setting = decrypt($setting);
            }
        });

        return $value;
    }

    /**
     * Remove secret keys from array.
     *
     * @param array $settings
     *
     * @return array
     */
    public function removeSecretKeys(array $settings)
    {
        foreach ($settings as $key => $value) {
            if ($this->isSecretKey($key)) {
                unset($settings[$key]);
            } elseif (is_object($value) || is_array($value)) {
                $settings[$key] = (array) $this->removeSecretKeys($value);
            }
        }

        return $settings;
    }

    /**
     * Choose only active company integrations.
     *
     * @param Builder $q
     */
    public function scopeActive($q)
    {
        $q->where('active', 1);
    }

    /**
     * Choose only disabled company integrations.
     *
     * @param Builder $q
     */
    public function scopeDisabled($q)
    {
        $q->where('active', 0);
    }

    /**
     * Choose only company integrations of given type.
     *
     * @param Builder $q
     * @param $type
     */
    public function scopeOfType($q, $type)
    {
        $q->whereHas('provider', function ($q) use ($type) {
            $q->where('type', $type);
        });
    }

    /**
     * Choose only company integrations of given type.
     *
     * @param Builder $q
     * @param $type
     */
    public function scopeByTimeTracker($q, $tracker)
    {
        $q->whereHas('provider', function ($q) use ($tracker) {
            $q->where('slug', $tracker);
        });
    }

    /**
     * Choose only company integrations of given type.
     *
     * @param Builder $q
     * @param $type
     */
    public function scopeByCompanyId($q, $company_id)
    {
        $q->where('company_id', $company_id);
    }

    /**
     * Verify whether key might contain secret data.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isSecretKey($key)
    {
        return Str::contains($key, ['password', 'token']);
    }
}
