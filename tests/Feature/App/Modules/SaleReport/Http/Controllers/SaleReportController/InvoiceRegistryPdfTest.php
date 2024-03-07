<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\Company as ModelCompany;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use File;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class InvoiceRegistryPdfTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function invoicesRegistryPdf_correct_data()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/invoice.pdf');
        $text_file = storage_path('tests/invoice.txt');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($text_file)) {
            File::delete($text_file);
        }
        $this->assertFalse(File::exists($file));
        $this->assertFalse(File::exists($text_file));

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'company_name';
        $company->main_address_street = 'sielska';
        $company->main_address_number = '10';
        $company->main_address_zip_code = '60-666';
        $company->main_address_city = 'poznan';
        $company->save();

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
            $invoices[0]->id => [
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
            ],
            $invoices[1]->id => [
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
            ],
        ];
        $this->assignInvoiceToCompany($invoices, $company);

        $array = [
            'Zapisy',
            'company_name',
            'sielska',
            10,
            'Data wydruku:',
            $now->format('Y-m-d'),
            '60-666',
            'poznan',
            'Lp',

            $invoices[0]->sale_date,
            $invoices[0]->number,
            'AF123456',

            $invoice_tax_reports[$invoices[0]->id][1]->vatRate->name,
            separators_format_output($invoice_tax_reports[$invoices[0]->id][1]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[0]->id][1]->price_gross -
                $invoice_tax_reports[$invoices[0]->id][1]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[0]->id][1]->price_gross),

            $invoices[1]->sale_date,
            $invoices[1]->number,
            $invoices[1]->invoiceContractor->vatin,

            $invoice_tax_reports[$invoices[1]->id][1]->vatRate->name,
            separators_format_output($invoice_tax_reports[$invoices[1]->id][1]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[1]->id][1]->price_gross -
                $invoice_tax_reports[$invoices[1]->id][1]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[1]->id][1]->price_gross),

            'Razem',
            separators_format_output($invoice_tax_reports[$invoices[0]->id][0]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[0]->id][0]->price_gross -
                $invoice_tax_reports[$invoices[1]->id][0]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[0]->id][0]->price_gross),

            separators_format_output($invoice_tax_reports[$invoices[1]->id][0]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[1]->id][0]->price_gross -
                $invoice_tax_reports[$invoices[1]->id][0]->price_net),
            separators_format_output($invoice_tax_reports[$invoices[1]->id][0]->price_gross),

            'nazwisko',
        ];

        ob_start();

        $this->get('reports/invoices-registry-pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');

            $this->assertContainsOrdered($array, file_get_contents($text_file));
            File::deleteDirectory($directory);
        }
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
