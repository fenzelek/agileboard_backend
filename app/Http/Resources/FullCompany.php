<?php

namespace App\Http\Resources;

class FullCompany extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['companyModules'];

    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'name',
        'country_vatin_prefix_id',
        'vatin',
        'vat_payer',
        'vat_release_reason_id',
        'vat_release_reason_note',
        'email',
        'logotype',
        'blockade_company',
        'website',
        'phone',
        'force_calendar_to_complete',
        'enable_calendar',
        'enable_activity',
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
        'default_invoice_gross_counted',
        'creator_id',
        'editor_id',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['app_settings'] = $this->resource->appSettings();
        $data['vat_settings_is_editable'] = $this->resource->vatSettingsIsEditable();

        return $data;
    }
}
