<?php

namespace App\Http\Resources;

class Contractor extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'company_id',
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
        'default_payment_term_days',
        'default_payment_method_id',
        'is_used',
        'creator_id',
        'editor_id',
        'remover_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->created_at) {
            $data['created_at'] = $this->created_at->toDateTimeString();
        }
        if ($this->updated_at) {
            $data['updated_at'] = $this->updated_at->toDateTimeString();
        }
        if ($this->deleted_at) {
            $data['deleted_at'] = $this->deleted_at->toDateTimeString();
        }

        $data = $this->addSummaryFields($data);

        return $data;
    }

    /**
     * Add summary fields if they were calculated.
     *
     * @param \App\Models\Db\Model $object
     * @param array $data
     *
     * @return array
     */
    protected function addSummaryFields(array $data)
    {
        $object_array = $this->resource->toArray();
        collect([
            'payments_all',
            'payments_paid',
            'payments_paid_late',
            'payments_not_paid',
        ])->each(function ($summaryField) use ($object_array, &$data) {
            if (array_key_exists($summaryField, $object_array)) {
                $data[$summaryField] = (float) denormalize_price($object_array[$summaryField]);
            }
        });

        return $data;
    }
}
