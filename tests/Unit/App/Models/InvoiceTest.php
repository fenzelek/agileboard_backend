<?php

namespace Tests\Unit\App\Models;

use App\Models\Db\Company as ModelCompany;
use App\Models\Db\Invoice as ModelInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_doesnt_throw_exception_for_2_invoices_with_different_numbers_for_same_company()
    {
        $company = factory(ModelCompany::class)->create();
        $invoice = ModelInvoice::create([
            'company_id' => $company->id,
            'number' => 'AA111',
        ]);

        $this->assertSame(1, $company->invoices()->count());

        $invoice_other = ModelInvoice::create([
            'company_id' => $company->id,
            'number' => 'BB222',
        ]);

        $this->assertSame(2, $company->invoices()->count());

        $invoice_added = ModelInvoice::latest('id')->first();
        $this->assertSame('BB222', $invoice_added->number);
    }

    /** @test */
    public function it_throws_exception_for_2_invoices_with_same_number_for_same_company()
    {
        $company = factory(ModelCompany::class)->create();
        $invoice = ModelInvoice::create([
            'company_id' => $company->id,
            'number' => 'AA111',
        ]);

        $this->assertSame(1, $company->invoices()->count());

        try {
            $invoice_same_number = ModelInvoice::create([
                'company_id' => $company->id,
                'number' => 'AA111',
            ]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $e);
            $this->assertInstanceOf(\PDOException::class, $e);

            $this->assertSame(1, $company->invoices()->count());

            return;
        }

        $this->fail('It was possible to create 2 invoices with same number for company');
    }

    /** @test */
    public function it_doesnt_throw_exception_for_2_invoices_with_same_numbers_for_different_companies()
    {
        $company = factory(ModelCompany::class)->create();
        $invoice = ModelInvoice::create([
            'company_id' => $company->id,
            'number' => 'AA111',
        ]);

        $other_company = factory(ModelCompany::class)->create();

        $this->assertSame(1, $company->invoices()->count());
        $this->assertSame(0, $other_company->invoices()->count());

        $invoice_other = ModelInvoice::create([
            'company_id' => $other_company->id,
            'number' => 'BB222',
        ]);

        $this->assertSame(1, $company->invoices()->count());
        $this->assertSame(1, $other_company->invoices()->count());

        $invoice_added = ModelInvoice::latest('id')->first();
        $this->assertSame('BB222', $invoice_added->number);
    }
}
