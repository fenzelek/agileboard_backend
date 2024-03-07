<?php

namespace App\Models\Db;

use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Payers\Vat;
use App\Modules\SaleInvoice\Traits\FindBySlug;

class InvoiceType extends Model
{
    use FindBySlug;

    protected $guarded = [];

    /**
     * Get parent invoice type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentType()
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Get the invoice title for pdf.
     *
     * @return string
     */
    public function getTitle($is_vat_payer)
    {
        if ($this->isType(InvoiceTypeStatus::FINAL_ADVANCE)) {
            return $this->description;
        }

        return empty($this->parent_type_id) ? $this->getDescription($is_vat_payer) : $this->parentType->getDescription($is_vat_payer);
    }

    /**
     * Check if type is with margin association.
     *
     * @return bool
     */
    public function isMarginType()
    {
        return $this->slug == InvoiceTypeStatus::MARGIN
            || $this->slug == InvoiceTypeStatus::MARGIN_CORRECTION;
    }

    /**
     * Check if type is with reverse charge association.
     *
     * @return bool
     */
    public function isReverseChargeType()
    {
        return $this->slug == InvoiceTypeStatus::REVERSE_CHARGE
            || $this->slug == InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION;
    }

    /**
     * Check if type is given by slug.
     *
     * @param $slug
     *
     * @return bool
     */
    public function isType($slug)
    {
        return $this->slug == $slug;
    }

    /**
     * Check if Invoice Type is one of correction type.
     *
     * @return bool
     */
    public function isCorrectionType()
    {
        return $this->isType(InvoiceTypeStatus::CORRECTION)
            || $this->isType(InvoiceTypeStatus::ADVANCE_CORRECTION)
            || $this->isSubtypeOf(InvoiceTypeStatus::CORRECTION);
    }

    /**
     * Check if Invoice Type is one of advance type.
     *
     * @return bool
     */
    public function isAdvanceType()
    {
        return $this->isType(InvoiceTypeStatus::ADVANCE)
            || $this->isType(InvoiceTypeStatus::FINAL_ADVANCE);
    }

    /**
     * Check if Invoice Type is Subtype.
     *
     * @return bool
     */
    public function isSubtype()
    {
        return null !== $this->parent_type_id;
    }

    /**
     * Check if Invoice Type is subtype given by slug.
     *
     * @param $slug
     *
     * @return bool
     */
    public function isSubtypeOf($slug)
    {
        if (! $this->isSubtype()) {
            return false;
        }

        return $this->parentType->slug == $slug;
    }

    /**
     * Get Invoice Description consideration of company vat payer setting.
     *
     * @param $is_vat_payer
     *
     * @return string
     */
    public function getDescription($is_vat_payer)
    {
        if ($is_vat_payer) {
            return $this->description;
        }

        return $this->no_vat_description;
    }
}
