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

class P106E3AFieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_value_for_margin_procedure_when_used_product_margin_invoice()
    {
        $name_of_procedure = 'sample name of procedure';

        $margin_procedure = Mockery::mock(MarginProcedure::class);
        $margin_procedure->shouldReceive('isUsedProductArtOrAntiqueMargin')->once()->andReturn(true);
        $margin_procedure->shouldReceive('getName')->once()->andReturn($name_of_procedure);
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

        $this->findAndVerifyField($result, 'tns:P_106E_3A', $name_of_procedure);
    }

    /** @test */
    public function it_doesnt_add_field_for_tourism()
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

        $this->assertNull($this->findChildElement($result, 'tns:P_106E_3A'));
    }
}
