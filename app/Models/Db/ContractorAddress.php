<?php

namespace App\Models\Db;

class ContractorAddress extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Contractor.
     */
    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    /**
     * Get default name by concatenate inputs:
     * street, number, city.
     *
     * @param array $address
     * @return string
     */
    public static function getDefaultName(array $address): string
    {
        return $address['street'] . ' ' . $address['number'] . ', ' . $address['city'];
    }
}
