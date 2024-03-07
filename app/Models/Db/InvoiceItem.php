<?php

namespace App\Models\Db;

use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceItemPaid;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Traits\PriceNormalize;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use PriceNormalize;

    protected $fillable = [
        'invoice_id',
        'pkwiu',
        'company_service_id',
        'name',
        'type',
        'custom_name',
        'price_net',
        'price_net_sum',
        'price_gross',
        'price_gross_sum',
        'vat_rate',
        'vat_rate_id',
        'vat_sum',
        'quantity',
        'service_unit_id',
        'print_on_invoice',
        'description',
        'base_document_id',
        'creator_id',
        'is_correction',
        'position_corrected_id',
        'proforma_item_id',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function positionCorrected()
    {
        return $this->belongsTo(self::class, 'position_corrected_id');
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Invoice item belongs to one service unit.
     *
     * @return BelongsTo
     */
    public function serviceUnit()
    {
        return $this->belongsTo(ServiceUnit::class);
    }

    public function companyService()
    {
        return $this->belongsTo(CompanyService::class);
    }

    public function getPrintNameAttribute()
    {
        return $this->custom_name ?? $this->name;
    }

    /**
     * Get extra paid attribute for proforma items
     * The Sum of payment through Advance Invoice.
     *
     * @return InvoiceItemPaid
     */
    public function getPaidAttribute()
    {
        if (auth()->user()->selectedCompany()
                ->appSettings(ModuleType::INVOICES_ADVANCE_ENABLED)
            && $this->invoice->invoiceType->isType(InvoiceTypeStatus::PROFORMA)
        ) {
            $paid = self::where('proforma_item_id', $this->id)
                ->whereHas('invoice', function ($query) {
                    $query->withoutTrashed();
                })->selectRaw('SUM(price_gross_sum) as gross, SUM(price_net_sum) as net')
                ->first();
        }

        return new InvoiceItemPaid($paid ?? $this->newInstance());
    }
}
