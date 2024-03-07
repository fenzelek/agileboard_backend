<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Factory;

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Builders\FinalAdvance;
use App\Modules\SaleInvoice\Services\Factory\Builder;
use App\Modules\SaleInvoice\Services\Builders\Margin;
use App\Modules\SaleInvoice\Services\Builders\MarginCorrection;
use App\Modules\SaleInvoice\Services\Builders\ReverseCharge;
use App\Modules\SaleInvoice\Services\Builders\ReverseChargeCorrection;
use App\Modules\SaleInvoice\Services\Builders\Advance;
use App\Modules\SaleInvoice\Services\Builders\AdvanceCorrection;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function get_margin_builder()
    {
        $margin_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($margin_invoice_type->id);
        $this->assertInstanceOf(Margin::class, $builder);
    }

    /** @test */
    public function get_margin_correction_builder()
    {
        $margin_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($margin_invoice_type->id);
        $this->assertInstanceOf(MarginCorrection::class, $builder);
    }

    /** @test */
    public function get_reverse_charge_builder()
    {
        $reverse_charge_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($reverse_charge_invoice_type->id);
        $this->assertInstanceOf(ReverseCharge::class, $builder);
    }

    /** @test */
    public function get_reverse_charge_correction_builder()
    {
        $reverse_charge_correction_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($reverse_charge_correction_invoice_type->id);
        $this->assertInstanceOf(ReverseChargeCorrection::class, $builder);
    }

    /** @test */
    public function get_advance_builder()
    {
        $margin_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($margin_invoice_type->id);
        $this->assertInstanceOf(Advance::class, $builder);
    }

    /** @test */
    public function get_advance_correction_builder()
    {
        $margin_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($margin_invoice_type->id);
        $this->assertInstanceOf(AdvanceCorrection::class, $builder);
    }

    /** @test */
    public function get_final_builder()
    {
        $margin_invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE);
        $factory_builder = app()->make(Builder::class);
        $builder = $factory_builder->create($margin_invoice_type->id);
        $this->assertInstanceOf(FinalAdvance::class, $builder);
    }
}
