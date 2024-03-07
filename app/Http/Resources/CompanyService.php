<?php

namespace App\Http\Resources;

use App\Models\Db\Model;

class CompanyService extends AbstractResource
{
    protected $fields = [
        'id',
        'company_id',
        'name',
        'type',
        'print_on_invoice',
        'description',
        'pkwiu',
        'price_net',
        'price_gross',
        'vat_rate_id',
        'service_unit_id',
        'is_used',
        'creator_id',
        'editor_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Transformer include denormalize price.
     *
     * @param Model $object
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['price_net'] = denormalize_price($this->price_net);
        $data['price_gross'] = denormalize_price($this->price_gross);

        return $data;
    }
}
