<?php

namespace App\Modules\SaleInvoice\Traits;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\ModuleType;
use App\Models\Db\Company;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

trait ModulesRules
{
    /**
     * Add common request rule for invoice depending on application settings.
     *
     * @param $rules
     * @return mixed
     */
    protected function requestRule($rules)
    {
        if ($this->canSetDeliveryAddress() && ! $this->isType(InvoiceTypeStatus::CORRECTION)) {
            $rules['delivery_address_id'] = ['required', 'delivery_address:' . auth()->user()->getSelectedCompanyId()];
            $rules['default_delivery'] = ['required', 'boolean'];
        }
        // If custom names for invoice items is on add validation rule
        if ($this->canCustomizeNameOfServices()) {
            $rules['items.*.custom_name'] = ['present', 'string', 'max:255'];
        }
        if ($this->canIssuingMargin()) {
            $rules = $this->marginTypeRules($rules);
        }
        if ($this->canIssuingReverseCharge()) {
            $rules = $this->reverseChargeTypeRules($rules);
        }
        if ($this->canIssuingAdvance()) {
            $rules = $this->advanceTypeRules($rules);
        }

        return $rules;
    }

    /**
     * Invoice can set delivery Addresses.
     *
     * @return bool
     */
    protected function canSetDeliveryAddress()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED);
    }

    /**
     * Invoice can  customize name of company services.
     *
     * @return bool
     */
    protected function canCustomizeNameOfServices()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE);
    }

    /**
     * Application can issuing Proforma.
     *
     * @return bool
     */
    protected function canIssuingProforma()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_PROFORMA_ENABLED);
    }

    /**
     * Application can issuing invoice margin and invoice correction margin.
     *
     * @return bool
     */
    protected function canIssuingMargin()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_MARGIN_ENABLED);
    }

    /**
     * Application can issuing invoice reverse charge and invoice correction reverse charge.
     *
     * @return bool
     */
    protected function canIssuingReverseCharge()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_REVERSE_CHARGE_ENABLED);
    }

    /**
     * Application can issuing advance invoice and advance invoice correction.
     *
     * @return bool
     */
    protected function canIssuingAdvance()
    {
        return $this->checkApplicationSetting(ModuleType::INVOICES_ADVANCE_ENABLED);
    }

    /**
     * Get allowing issuing invoice type by application settings.
     * @return Collection
     */
    protected function allowInvoiceTypes()
    {
        $block_types = [];
        if (! $this->canIssuingProforma()) {
            $block_types[] = InvoiceTypeStatus::PROFORMA;
        }
        if (! $this->canIssuingMargin()) {
            $block_types[] = InvoiceTypeStatus::MARGIN;
            $block_types[] = InvoiceTypeStatus::MARGIN_CORRECTION;
        }
        if (! $this->canIssuingReverseCharge()) {
            $block_types[] = InvoiceTypeStatus::REVERSE_CHARGE;
            $block_types[] = InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION;
        }
        if (! $this->canIssuingAdvance()) {
            $block_types[] = InvoiceTypeStatus::ADVANCE;
            $block_types[] = InvoiceTypeStatus::ADVANCE_CORRECTION;
            $block_types[] = InvoiceTypeStatus::FINAL_ADVANCE;
        }
        $invoice_type_model = app()->make(InvoiceType::class);

        return $invoice_type_model->whereNotIn('slug', $block_types)->pluck('id');
    }
}
