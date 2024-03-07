<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use Carbon\Carbon;
use Tests\Helpers\Jpk;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class OkresFaKorygowanejFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_doesnt_add_correction_period_for_non_correction_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::VAT])
        );

        $result = $this->buildAndCreateResult($invoice);

        $this->assertNull($this->findChildElement($result, 'tns:OkresFaKorygowanej'));
    }

    /** @test */
    public function it_doesnt_add_correction_period_for_correction_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->correction_type = InvoiceCorrectionType::TAX;
        $invoice->issue_date = Carbon::parse('2017-01-01');
        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::CORRECTION])
        );
        $invoice->setRelation('correctedInvoice', new InvoiceModel());
        $corrected_invoice_date_issue = Carbon::parse('2016-01-01');
        $corrected_invoice = new Invoice(['issue_date' => $corrected_invoice_date_issue]);
        $invoice->setRelation('correctedInvoice', $corrected_invoice);
        $result = $this->buildAndCreateResult($invoice);
        $this->findAndVerifyField($result, 'tns:OkresFaKorygowanej', 'Od ' . $invoice->correctedInvoice->issue_date->format('Y-m-d') . ' do ' . $invoice->issue_date->format('Y-m-d'));
    }
}
