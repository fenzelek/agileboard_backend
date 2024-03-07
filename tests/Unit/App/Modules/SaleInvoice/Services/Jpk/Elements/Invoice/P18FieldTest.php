<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Tests\Helpers\Jpk;
use Tests\TestCase;

class P18FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_reverse_charge_to_true_when_reverse_charge()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE])
        );
        $invoice->setRelation('invoiceReverseCharge', new InvoiceReverseCharge());

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_18', 'true');
    }

    /** @test */
    public function it_sets_reverse_charge_to_true_when_reverse_charge_correction()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION])
        );
        $invoice->setRelation('invoiceReverseCharge', new InvoiceReverseCharge());

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_18', 'true');
    }

    /** @test */
    public function it_sets_reverse_charge_to_false_when_normal_vat_invoice()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation('invoiceType', new InvoiceType(['slug' => InvoiceTypeStatus::VAT]));

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_18', 'false');
    }
}
