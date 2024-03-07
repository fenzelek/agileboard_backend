<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\InvoicesSelector;

use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Services\Jpk\InvoicesSelector;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_only_invoices_that_belong_to_given_company()
    {
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $invoice_selector = app()->make(InvoicesSelector::class);

        $company = factory(Company::class)->create(['id' => 512]);
        $other_company = factory(Company::class)->create(['id' => 523]);

        $invoices = factory(Invoice::class, 6)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
        ]);

        $other_company_invoices = factory(Invoice::class, 7)->create([
            'company_id' => $other_company->id,
            'sale_date' => $start_date,
        ]);

        $response = $invoice_selector->get($company, $start_date, $end_date);

        $this->verifyResponse($response, $invoices);
    }

    /** @test */
    public function it_returns_only_not_deleted_invoices()
    {
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $invoice_selector = app()->make(InvoicesSelector::class);

        $company = factory(Company::class)->create(['id' => 512]);

        $not_deleted_invoices = factory(Invoice::class, 6)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'deleted_at' => null,
        ]);

        $deleted_invoices = factory(Invoice::class, 7)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'deleted_at' => $start_date,
        ]);

        $response = $invoice_selector->get($company, $start_date, $end_date);

        $this->verifyResponse($response, $not_deleted_invoices);
    }

    /** @test */
    public function it_doesnt_contain_proforma_invoices()
    {
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $invoice_selector = app()->make(InvoicesSelector::class);

        $company = factory(Company::class)->create(['id' => 512]);

        $vat = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);

        $correction = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
        ]);

        $proforma = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $margin = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);

        $margin_correction = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id,
        ]);

        $reverse_charge = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
        ]);

        $reverse_charge_correction = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION)->id,
        ]);

        $advance = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);

        $advance_correction = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE_CORRECTION)->id,
        ]);

        $final_advance = $not_deleted_invoices = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'sale_date' => $start_date,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
        ]);

        $expected_invoices = new Collection([
            $vat,
            $correction,
            $margin,
            $margin_correction,
            $reverse_charge,
            $reverse_charge_correction,
            $advance,
            $advance_correction,
            $final_advance,
        ]);

        $response = $invoice_selector->get($company, $start_date, $end_date);

        $this->verifyResponse($response, $expected_invoices);
    }

    /** @test */
    public function it_returns_only_invoices_filtered_by_sale_date()
    {
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $invoice_selector = app()->make(InvoicesSelector::class);

        $company = factory(Company::class)->create(['id' => 512]);

        $before = factory(Invoice::class, 3)->create([
            'company_id' => $company->id,
            'sale_date' => '2016-04-21',
        ]);

        $start = factory(Invoice::class, 4)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-01-01',
        ]);

        $middle = factory(Invoice::class, 5)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-04-01',
        ]);

        $end = factory(Invoice::class, 6)->create([
            'company_id' => $company->id,
            'sale_date' => $end_date,
        ]);

        $after = factory(Invoice::class, 7)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-12-09',
        ]);

        $expected_invoices = $start->merge($middle)->merge($end);

        $response = $invoice_selector->get($company, $start_date, $end_date);

        $this->verifyResponse($response, $expected_invoices);
    }

    protected function verifyResponse($response, Collection $expected_invoices)
    {
        $this->assertTrue($response instanceof Collection);
        $this->assertCount($expected_invoices->count(), $response);

        $expected_invoices->each(function ($invoice, $key) use ($response) {
            $this->assertSame(
                $invoice->fresh()->attributesToArray(),
                $response[$key]->attributesToArray()
            );
        });
    }
}
