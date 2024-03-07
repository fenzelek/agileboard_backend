<?php

namespace App\Models\Db;

use App\Interfaces\CompanyInterface;
use DateTimeInterface;

/**
 * Class Model.
 *
 * This is base class that should be used for all models in case any
 * functionality should be added to all models
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Fields for which null will be set in case of empty value.
     *
     * @var array
     */
    protected $nullable = [];

    /**
     * Scope a query to only include object in company.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param CompanyInterface $object
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCompany($query, CompanyInterface $object)
    {
        return $this->scopeCompanyId($query, $object->getCompanyId());
    }

    public function scopeCompanyId($query, $company_id)
    {
        return $query->where('company_id', $company_id);
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Listen for save event.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            self::setNullables($model);
        });
    }

    /**
     * Set empty nullable fields to null.
     *
     * @param object $model
     */
    protected static function setNullables($model)
    {
        foreach ($model->nullable as $field) {
            if (empty($model->{$field})) {
                $model->{$field} = null;
            }
        }
    }
}
