<?php

namespace App\Modules\SaleInvoice\Services\Factory;

use App\Interfaces\BuilderCreateInvoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Builders\Advance;
use App\Modules\SaleInvoice\Services\Builders\AdvanceCorrection;
use App\Modules\SaleInvoice\Services\Builders\Correction;
use App\Modules\SaleInvoice\Services\Builders\FinalAdvance;
use App\Modules\SaleInvoice\Services\Builders\Margin;
use App\Modules\SaleInvoice\Services\Builders\MarginCorrection;
use App\Modules\SaleInvoice\Services\Builders\Proforma;
use App\Modules\SaleInvoice\Services\Builders\ReverseCharge;
use App\Modules\SaleInvoice\Services\Builders\ReverseChargeCorrection;
use App\Modules\SaleInvoice\Services\Builders\Vat;
use Illuminate\Foundation\Application;

class Builder extends Method
{
    /**
     * @var InvoiceType
     */
    protected $invoice_type;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Builder constructor.
     *
     * @param InvoiceType $invoice_type
     */
    public function __construct(InvoiceType $invoice_type, Application $app)
    {
        $this->invoice_type = $invoice_type;
        $this->app = $app;
    }

    /**
     * Create new instance of CreateInvoice Builders.
     *
     * @param int $type_id
     *
     * @return BuilderCreateInvoice
     */
    protected function createBuilder(int $type_id): BuilderCreateInvoice
    {
        $invoice_type = $this->invoice_type->where('id', $type_id)->first();
        if (empty($invoice_type)) {
            throw new \InvalidArgumentException("$type_id is not a valid invoice type");
        }
        switch ($invoice_type->slug) {
            case InvoiceTypeStatus::PROFORMA:
                return $this->app->make(Proforma::class);
            case InvoiceTypeStatus::VAT:
                return $this->app->make(Vat::class);
            case InvoiceTypeStatus::CORRECTION:
                return $this->app->make(Correction::class);
            case InvoiceTypeStatus::MARGIN:
                return $this->app->make(Margin::class);
            case InvoiceTypeStatus::MARGIN_CORRECTION:
                return $this->app->make(MarginCorrection::class);
            case InvoiceTypeStatus::REVERSE_CHARGE:
                return $this->app->make(ReverseCharge::class);
            case InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION:
                return $this->app->make(ReverseChargeCorrection::class);
            case InvoiceTypeStatus::ADVANCE:
                return $this->app->make(Advance::class);
            case InvoiceTypeStatus::ADVANCE_CORRECTION:
                return $this->app->make(AdvanceCorrection::class);
            case InvoiceTypeStatus::FINAL_ADVANCE:
                return $this->app->make(FinalAdvance::class);
            default:
                throw new \InvalidArgumentException("$type_id is not a valid invoice type");

        }
    }
}
