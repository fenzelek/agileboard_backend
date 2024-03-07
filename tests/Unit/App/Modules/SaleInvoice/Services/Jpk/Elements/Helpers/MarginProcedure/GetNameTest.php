<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;

use App\Models\Other\InvoiceMarginProcedureType;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\MarginProcedure;
use Tests\TestCase;

class GetNameTest extends TestCase
{
    /** @test */
    public function it_returns_valid_type_for_used_product()
    {
        $margin_procedure = new MarginProcedure();
        $this->assertSame('procedura marży - towary używane', $margin_procedure->getName(InvoiceMarginProcedureType::USED_PRODUCT));
    }

    /** @test */
    public function it_returns_valid_type_for_art()
    {
        $margin_procedure = new MarginProcedure();
        $this->assertSame('procedura marży - dzieła sztuki', $margin_procedure->getName(InvoiceMarginProcedureType::ART));
    }

    /** @test */
    public function it_returns_valid_type_for_antique()
    {
        $margin_procedure = new MarginProcedure();
        $this->assertSame('procedura marży - przedmioty kolekcjonerskie i antyki', $margin_procedure->getName(InvoiceMarginProcedureType::ANTIQUE));
    }

    /** @test */
    public function it_returns_null_for_tourism()
    {
        $margin_procedure = new MarginProcedure();
        $this->assertSame(null, $margin_procedure->getName(InvoiceMarginProcedureType::TOUR_OPERATOR));
    }
}
