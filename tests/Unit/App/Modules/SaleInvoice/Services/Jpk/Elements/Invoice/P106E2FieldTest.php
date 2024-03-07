<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;
use Mockery;
use Tests\Helpers\Jpk;
use Tests\TestCase;

class P106E2FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_true_when_margin_procedure_returned_true()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->once()->andReturn(false);
        $margin_procedure->shouldNotReceive('getName');
        $margin_procedure->shouldReceive('isTourOperatorMargin')->once()->andReturn(true);

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::TOUR_OPERATOR])
        );

        $result = $this->buildAndCreateResult($invoice, null, null, $margin_procedure);

        $this->findAndVerifyField($result, 'tns:P_106E_2', 'true');
    }

    /** @test */
    public function it_sets_false_when_margin_procedure_returned_false()
    {
        $invoice = $this->getDefaultInvoiceModel();

        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->once()->andReturn(false);
        $margin_procedure->shouldNotReceive('getName');
        $margin_procedure->shouldReceive('isTourOperatorMargin')->once()->andReturn(false);

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::TOUR_OPERATOR])
        );

        $result = $this->buildAndCreateResult($invoice, null, null, $margin_procedure);

        $this->findAndVerifyField($result, 'tns:P_106E_2', 'false');
    }
}
