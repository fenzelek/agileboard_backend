<?php

namespace App\Models\Db;

use App\Models\Other\DatePeriod;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleOther\Traits\PriceNormalize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

class Invoice extends Model
{
    use Filterable;
    use PriceNormalize;
    use SoftDeletes;

    const TYPE_VAT = 'vat';
    const TYPE_CORRECTION = 'correction';

    protected $fillable = [
        'number',
        'order_number',
        'invoice_registry_id',
        'drawer_id',
        'company_id',
        'contractor_id',
        'corrected_invoice_id',
        'correction_type',
        'proforma_id',
        'invoice_margin_procedure_id',
        'invoice_reverse_charge_id',
        'sale_date',
        'issue_date',
        'order_number_date',
        'invoice_type_id',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_left',
        'payment_term_days',
        'payment_method_id',
        'bank_account_id',
        'paid_at',
        'gross_counted',
        'description',
    ];

    protected $dates = ['deleted_at', 'paid_at'];

    public function scopePaidLate($query)
    {
        return $query->whereRaw('paid_at IS NOT NULL AND (DATE(DATE_ADD(issue_date, INTERVAL payment_term_days DAY)) < DATE(paid_at))');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function receipts()
    {
        return $this->belongsToMany(Receipt::class, 'invoice_receipt')->withTimestamps();
    }

    public function onlineSales()
    {
        return $this->belongsToMany(OnlineSale::class, 'invoice_online_sale')->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function drawer()
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceCompany()
    {
        return $this->hasOne(InvoiceCompany::class);
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function parentInvoices()
    {
        return $this->belongsToMany(self::class, null, 'node_id', 'parent_id')->withTimestamps();
    }

    public function nodeInvoices()
    {
        return $this->belongsToMany(self::class, null, 'parent_id', 'node_id')->withTimestamps();
    }

    public function invoiceContractor()
    {
        return $this->hasOne(InvoiceContractor::class);
    }

    public function correctedInvoice()
    {
        return $this->belongsTo(self::class, 'corrected_invoice_id')->withTrashed();
    }

    public function taxes()
    {
        return $this->hasMany(InvoiceTaxReport::class);
    }

    public function finalAdvanceTaxes()
    {
        return $this->hasMany(InvoiceFinalAdvanceTaxReport::class);
    }

    public function taxesRate($vat_rate_id)
    {
        return $this->hasMany('taxes')->where('vat_rate_id', $vat_rate_id);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function invoiceType()
    {
        return $this->belongsTo(InvoiceType::class);
    }

    /**
     * Get relation to indicate margin procedure.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceMarginProcedure()
    {
        return $this->belongsTo(InvoiceMarginProcedure::class);
    }

    /**
     * Invoice might belong to invoice reverse charge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceReverseCharge()
    {
        return $this->belongsTo(InvoiceReverseCharge::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Get special partial payment for the invoice.
     *
     * @return InvoicePayment
     */
    public function specialPayments()
    {
        return $this->payments()->where('special_partial_payment', 1);
    }

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class);
    }

    public function correctionInvoice()
    {
        return $this->hasMany(self::class, 'corrected_invoice_id');
    }

    /**
     * Get relation to contractor address indicate as delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function deliveryAddress()
    {
        return $this->belongsTo(ContractorAddress::class);
    }

    /**
     * Get relation to added delivery address for invoice.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function invoiceDeliveryAddress()
    {
        return $this->hasOne(InvoiceDeliveryAddress::class);
    }

    /**
     * Get relation to proforma which was previous issuing.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function proforma()
    {
        return $this->belongsTo(self::class, 'proforma_id');
    }

    /**
     * Get all related invoices. If using for multiple records you should eager load parentInvoices
     * and nodeInvoices relationships first to lower number of database queries.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getInvoicesAttribute()
    {
        return $this->parentInvoices->merge($this->nodeInvoices);
    }

    public function isCollective()
    {
        if ($this->receipts()->count() > 1 || $this->onlineSales()->count() > 1) {
            return true;
        }

        return false;
    }

    public function isBilling()
    {
        return ! $this->isProforma();
    }

    /**
     * Check if invoice is Proforma.
     *
     * @return bool
     */
    public function isProforma()
    {
        return $this->invoiceType->isType(InvoiceTypeStatus::PROFORMA);
    }

    /**
     * Check if invoice is paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->paymentMethod->isInAdvance()
            || $this->receipts->count() > 1
            || $this->invoiceType->isType(InvoiceTypeStatus::ADVANCE)
            || $this->invoiceType->isType(InvoiceTypeStatus::FINAL_ADVANCE)
            || ! empty($this->paid_at);
    }

    /**
     * Get invoice items only of one type.
     *
     * @param string $service_type
     *
     * @return mixed
     */
    public function itemsType($service_type)
    {
        return $this->items->where('type', $service_type);
    }

    /*
     * Filter invoice by Sale Date for given period date.
     *
     * @param $query
     * @param DatePeriod $period
     * @return Builder
     */
    public function scopeFilterBySaleDate($query, DatePeriod $period)
    {
        return $this->filterByDate($query, 'sale_date', $period);
    }

    /**
     * Filter invoice by given Type Date for given period date.
     *
     * @param $query
     * @param $filter_date
     * @param DatePeriod $period
     *
     * @return Builder
     */
    public function filterByDate($query, $filter_date, DatePeriod $period)
    {
        return $query->whereDate($filter_date, '>=', $period->getStart())
            ->whereDate($filter_date, '<=', $period->getEnd());
    }

    /**
     * Check if Invoice can be edit.
     *
     * @return bool
     */
    public function isEditable()
    {
        return ! ($this->parentInvoices()->count()
            || $this->invoiceType->isCorrectionType());
    }

    /**
     * Find all Advance Invoices Included for Final Advance Invoice.
     */
    public function advanceInvoicesIncluded()
    {
        if (! $this->invoiceType->isType(InvoiceTypeStatus::FINAL_ADVANCE)) {
            return;
        }

        return self::where(
            'invoice_type_id',
            InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id
        )
            ->where('proforma_id', $this->proforma->id)
            ->get();
    }

    /**
     * Retrieve Taxes to printing on PDF.
     *
     * @return Collection
     */
    public function fullPrintTaxes()
    {
        if ($this->invoiceType->isType(InvoiceTypeStatus::FINAL_ADVANCE)) {
            return $this->finalAdvanceTaxes;
        }

        return $this->taxes;
    }

    /**
     * Get date until when payments should be made.
     *
     * @return Carbon
     */
    public function getPaymentDue()
    {
        return Carbon::parse($this->issue_date)->addDays($this->payment_term_days);
    }

    /**
     * Get paid amount (raw, not denormalized).
     *
     * @return int
     */
    public function getRawPaidAmount()
    {
        if ($this->price_gross >= 0) {
            return $this->price_gross - $this->payment_left;
        }

        // for correction invoices
        return $this->price_gross + abs($this->payment_left);
    }

    /**
     * Verify whether invoice is reverse charge with IN_EU_TRIPLE type.
     *
     * @return bool
     */
    public function isEuTripleReverseCharge()
    {
        return $this->invoiceType->isReverseChargeType() &&
            $this->invoiceReverseCharge->hasSlug(InvoiceReverseChargeType::IN_EU_TRIPLE);
    }

    /**
     * Check if tax details print on pdf.
     *
     * @return bool
     */
    public function printTaxDetails()
    {
        return $this->company->isVatPayer() && ! $this->invoiceType->isMarginType();
    }

    /**
     * Check if invoice has special payments.
     *
     * @return int
     */
    public function hasSpecialPayments()
    {
        return $this->specialPayments()->count();
    }

    /**
     * Get Sum of special payments.
     *
     * @return float
     */
    public function specialPaymentsAmount()
    {
        return $this->specialPayments()->sum('amount');
    }

    /**
     * Check if invoice has incomplete Special Payments.
     *
     * @return bool
     */
    public function hasIncompleteSpecialPayments()
    {
        if (! $this->hasSpecialPayments()) {
            return false;
        }

        return $this->specialPaymentsAmount() < $this->price_gross;
    }
}
