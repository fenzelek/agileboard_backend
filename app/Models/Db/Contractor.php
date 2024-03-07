<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use SoftDeletes, FullVatin;

    protected $fillable = [
        'name',
        'country_vatin_prefix_id',
        'vatin',
        'email',
        'phone',
        'bank_name',
        'bank_account_number',
        'main_address_street',
        'main_address_number',
        'main_address_zip_code',
        'main_address_city',
        'main_address_country',
        'contact_address_street',
        'contact_address_number',
        'contact_address_zip_code',
        'contact_address_city',
        'contact_address_country',
        'is_used',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Contractor can be assigned to multiple invoices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Contractor can have extra addresses (ie. delivery address).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(ContractorAddress::class);
    }

    /**
     * Get main address in table.
     *
     * @return array
     */
    public function getRawMainAddress(): array
    {
        return [
            'street' => $this->main_address_street,
            'number' => $this->main_address_number,
            'zip_code' => $this->main_address_zip_code,
            'city' => $this->main_address_city,
            'country' => $this->main_address_country,
        ];
    }
}
