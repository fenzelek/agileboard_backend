<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoiceType as InvoiceTypeModel;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;
use Tests\TestCase;

class IsUsedProductArtOrAntiqueMarginTest extends TestCase
{
    /** @test */
    public function it_returns_true_for_margin_used_product()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::USED_PRODUCT])
        );

        $margin_procedure = new MarginProcedure();

        $this->assertTrue($margin_procedure->isUsedProductArtOrAntiqueMargin($invoice));
    }

    /** @test */
    public function it_returns_true_for_margin_art()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::ART])
        );

        $margin_procedure = new MarginProcedure();

        $this->assertTrue($margin_procedure->isUsedProductArtOrAntiqueMargin($invoice));
    }

    /** @test */
    public function it_returns_true_for_margin_antique()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::ANTIQUE])
        );

        $margin_procedure = new MarginProcedure();

        $this->assertTrue($margin_procedure->isUsedProductArtOrAntiqueMargin($invoice));
    }

    /** @test */
    public function it_returns_false_for_margin_tour_operator()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::MARGIN])
        );
        $invoice->setRelation(
            'invoiceMarginProcedure',
            new InvoiceMarginProcedure(['slug' => InvoiceMarginProcedureType::TOUR_OPERATOR])
        );

        $margin_procedure = new MarginProcedure();

        $this->assertFalse($margin_procedure->isUsedProductArtOrAntiqueMargin($invoice));
    }

    /** @test */
    public function it_returns_false_for_vat_invoice()
    {
        $invoice = new Invoice();
        $invoice->setRelation(
            'invoiceType',
            new InvoiceTypeModel(['slug' => InvoiceTypeStatus::VAT])
        );

        $margin_procedure = new MarginProcedure();

        $this->assertFalse($margin_procedure->isUsedProductArtOrAntiqueMargin($invoice));
    }
}
