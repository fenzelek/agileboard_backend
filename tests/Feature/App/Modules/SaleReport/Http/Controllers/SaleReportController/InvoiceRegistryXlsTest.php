<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Imports\InvoicesImport;
use App\Models\Db\Company as ModelCompany;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use File;
use Excel;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class InvoiceRegistryXlsTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /** @test */
    public function invoicesRegistryXls_user_has_permission()
    {
        $this->app['config']->set('enable_test_xls', true);
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('reports/invoices-registry-xls?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function invoicesRegistryXls_correct_data()
    {
        $this->app['config']->set('enable_test_xls', true);
        $directory = storage_path('tests');
        $file = storage_path('tests/invoices-registry.xls');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }

        $this->assertFalse(File::exists($file));

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = factory(Invoice::class, 2)->create();
        $contractors = [];
        $invoice_tax_reports = [];
        $incoming_data = [
            $invoices[0]->id => [
                [
                    'price_net' => 150,
                    'price_gross' => 300,
                ],
                [
                    'price_net' => -100,
                    'price_gross' => -300,
                ],

            ],
            $invoices[1]->id => [
                [
                    'price_net' => 100,
                    'price_gross' => 300,
                ],
                [
                    'price_net' => 50,
                    'price_gross' => -300,
                ],
            ],
        ];
        $day = 1;
        foreach ($invoices as $invoice) {
            $invoice->sale_date = Carbon::create(2017, 01, $day)->toDateString();
            $invoice->number = 'AABB' . $day;
            $invoice->save();
            $contractors[] = factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $invoice->contractor_id,
                'vatin' => 123456,
                'country_vatin_prefix_id' => 1,
            ]);
            $invoice_tax_reports[$invoice->id] = factory(InvoiceTaxReport::class, 2)->create([
                'invoice_id' => $invoice->id,
            ]);
            $index = 1;
            foreach ($invoice_tax_reports[$invoice->id] as $key => $item) {
                $item->vatRate->name = 'vat_rate_' . $day . $index;
                $item->vatRate->save();
                $item->price_net = $incoming_data[$invoice->id][$key]['price_net'];
                $item->price_gross = $incoming_data[$invoice->id][$key]['price_gross'];
                $item->save();
                ++$index;
            }
            ++$day;
        }
        $invoice_tax_reports_expect = [
            [
                'vat_sum' => 1.5,
                'price_net' => 1.5,
                'price_gross' => 3,
            ],
            [
                'vat_sum' => -2,
                'price_net' => -1,
                'price_gross' => -3,
            ],
            [
                'vat_sum' => 2,
                'price_net' => 1,
                'price_gross' => 3,
            ],
            [
                'vat_sum' => -3.5,
                'price_net' => 0.5,
                'price_gross' => -3,
            ],
        ];

        $invoice_single_vat_rate = factory(Invoice::class)->create([
            'sale_date' => Carbon::create(2017, 01, 30)->toDateString(),
            'number' => 'AABBZZ1',
            'company_id' => $company->id,
        ]);
        $contractor_single_vat_rate = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice_single_vat_rate->id,
            'contractor_id' => $invoice_single_vat_rate->contractor_id,
        ]);
        $invoice_single_vat_rate_tax_reports = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice_single_vat_rate->id,
            'price_net' => 1000,
            'price_gross' => 1460,
        ]);

        $invoice_triple_vat_rate = factory(Invoice::class)->create([
            'sale_date' => Carbon::create(2017, 03, 30)->toDateString(),
            'number' => 'AABBXXYYZZ1',
            'company_id' => $company->id,
        ]);
        $contractor_triple_vat_rate = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice_triple_vat_rate->id,
            'contractor_id' => $invoice_triple_vat_rate->contractor_id,
        ]);
        $invoice_tax_reports_triple_vat_rate = factory(InvoiceTaxReport::class, 3)->create([
            'invoice_id' => $invoice_triple_vat_rate->id,
        ]);

        $invoice_tax_reports_triple_vat_rate[0]->update([
            'price_net' => 50,
            'price_gross' => 150,
        ]);
        $invoice_tax_reports_triple_vat_rate[1]->update([
            'price_net' => 150,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports_triple_vat_rate[2]->update([
            'price_net' => 200,
            'price_gross' => 500,
        ]);
        $invoice_triple_vat_rate_expect = [
            [
                'price_net' => 0.5,
                'price_gross' => 1.5,
                'vat_sum' => 1,
            ],
            [
                'price_net' => 1.5,
                'price_gross' => 3,
                'vat_sum' => 1.5,

            ],
            [
                'price_net' => 2,
                'price_gross' => 5,
                'vat_sum' => 3,
            ],
        ];

        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry-xls?selected_company_id=' . $company->id)
            ->assertResponseOk();

        if (config('enable_test_xls')) {
            $result = Excel::toArray(new InvoicesImport(), 'data-export.xls', 'local', \Maatwebsite\Excel\Excel::XLS)[0];

            for ($i = 0, $item = 0; $i < 4;$i++, $item = floor($i / 2)) {
                $this->assertEquals($invoice_tax_reports_expect[$i]['vat_sum'], $result[$i]['kwota_vat']);
                $this->assertEquals($invoice_tax_reports_expect[$i]['price_net'], $result[$i]['netto']);
                $this->assertEquals($invoice_tax_reports_expect[$i]['price_gross'], $result[$i]['brutto']);

                $this->assertEquals($item + 1, $result[$i]['lp']);
                $this->assertSame($invoices[$item]->sale_date, $result[$i]['data']);
                $this->assertSame($invoices[$item]->number, $result[$i]['nr_dokumentu']);
                $this->assertSame($invoices[$item]->invoiceContractor->name, $result[$i]['nazwa_kontrahenta']);
                $this->assertSame($invoices[$item]->invoiceContractor->main_address_street, $result[$i]['ulica']);
                $this->assertEquals($invoices[$item]->invoiceContractor->main_address_number, $result[$i]['numer_domu']);
                $this->assertSame($invoices[$item]->invoiceContractor->main_address_zip_code, $result[$i]['kod_pocztowy']);
                $this->assertSame($invoices[$item]->invoiceContractor->main_address_city, $result[$i]['miejscowosc']);
                $this->assertSame($invoices[$item]->invoiceContractor->main_address_country, $result[$i]['kraj']);
                $this->assertEquals('AF123456', $result[$i]['nip']);
            }

            $this->assertEquals(4.6, $result[4]['kwota_vat']);
            $this->assertEquals(10, $result[4]['netto']);
            $this->assertEquals(14.6, $result[4]['brutto']);
            $this->assertEquals(3, $result[4]['lp']);
            $this->assertSame($invoice_single_vat_rate->sale_date, $result[4]['data']);
            $this->assertSame($invoice_single_vat_rate->number, $result[4]['nr_dokumentu']);
            $this->assertSame($invoice_single_vat_rate->invoiceContractor->name, $result[4]['nazwa_kontrahenta']);
            $this->assertSame($invoice_single_vat_rate->invoiceContractor->main_address_street, $result[4]['ulica']);
            $this->assertEquals($invoice_single_vat_rate->invoiceContractor->main_address_number, $result[4]['numer_domu']);
            $this->assertSame($invoice_single_vat_rate->invoiceContractor->main_address_zip_code, $result[4]['kod_pocztowy']);
            $this->assertSame($invoice_single_vat_rate->invoiceContractor->main_address_city, $result[4]['miejscowosc']);
            $this->assertSame($invoice_single_vat_rate->invoiceContractor->main_address_country, $result[4]['kraj']);
            $this->assertEquals($invoice_single_vat_rate->invoiceContractor->vatin, $result[4]['nip']);

            $item = 0;
            for ($i = 5; $i < 8; $i++) {
                $this->assertEquals($invoice_triple_vat_rate_expect[$item]['vat_sum'], $result[$i]['kwota_vat']);
                $this->assertEquals($invoice_triple_vat_rate_expect[$item]['price_net'], $result[$i]['netto']);
                $this->assertEquals($invoice_triple_vat_rate_expect[$item]['price_gross'], $result[$i]['brutto']);

                $this->assertEquals(4, $result[$i]['lp']);
                $this->assertSame($invoice_triple_vat_rate->sale_date, $result[$i]['data']);
                $this->assertSame($invoice_triple_vat_rate->number, $result[$i]['nr_dokumentu']);
                $this->assertSame($invoice_triple_vat_rate->invoiceContractor->name, $result[$i]['nazwa_kontrahenta']);
                $this->assertSame($invoice_triple_vat_rate->invoiceContractor->main_address_street, $result[$i]['ulica']);
                $this->assertEquals($invoice_triple_vat_rate->invoiceContractor->main_address_number, $result[$i]['numer_domu']);
                $this->assertSame($invoice_triple_vat_rate->invoiceContractor->main_address_zip_code, $result[$i]['kod_pocztowy']);
                $this->assertSame($invoice_triple_vat_rate->invoiceContractor->main_address_city, $result[$i]['miejscowosc']);
                $this->assertSame($invoice_triple_vat_rate->invoiceContractor->main_address_country, $result[$i]['kraj']);
                $this->assertEquals($invoice_triple_vat_rate->invoiceContractor->vatin, $result[$i]['nip']);
                ++$item;
            }
            File::deleteDirectory($directory);
        }
    }

    /** @test */
    public function invoicesRegistryXls_if_disable_test_mode_return_error_500_during_try_download()
    {
        $this->app['config']->set('enable_test_xls', false);

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('reports/invoices-registry-xls?selected_company_id=' . $company->id)
                ->assertResponseStatus(500);
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        return $company;
    }

    protected function assignInvoiceToCompany(Collection $invoices, ModelCompany $company)
    {
        $invoices->each(function ($invoice) use ($company) {
            $invoice->company_id = $company->id;
            $invoice->save();
        });
    }

    protected function createInvoicesAndAssignToCompany(ModelCompany $company)
    {
        $invoices = factory(Invoice::class, 2)->create();

        $contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoices[0]->id,
            'contractor_id' => $invoices[0]->contractor_id,
        ]);

        $contractor_2 = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoices[1]->id,
            'contractor_id' => $invoices[1]->contractor_id,
        ]);

        $this->assignInvoiceToCompany($invoices, $company);

        return $invoices;
    }
}
