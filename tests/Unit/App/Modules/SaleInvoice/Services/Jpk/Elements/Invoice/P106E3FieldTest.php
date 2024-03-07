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

class P106E3FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_non_tourism_field_to_true_when_margin_procedure_returns_true()
    {
        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->once()->andReturn(true);
        $margin_procedure->shouldReceive('getName')->once();
        $margin_procedure->shouldReceive('isTourOperatorMargin')->andReturn(false);

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::USED_PRODUCT])
        );

        $result = $this->buildAndCreateResult($invoice, null, null, $margin_procedure);

        $this->findAndVerifyField($result, 'tns:P_106E_3', 'true');
    }

    /** @test */
    public function it_sets_non_tourism_field_to_false_when_margin_procedure_returns_false()
    {
        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->once()->andReturn(false);
        $margin_procedure->shouldNotReceive('getName');
        $margin_procedure->shouldReceive('isTourOperatorMargin')->andReturn(false);

        $invoice = $this->getDefaultInvoiceModel();

        $invoice->setRelation(
            'invoiceType',
            new InvoiceType(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::USED_PRODUCT])
        );

        $result = $this->buildAndCreateResult($invoice, null, null, $margin_procedure);

        $this->findAndVerifyField($result, 'tns:P_106E_3', 'false');
    }
}
