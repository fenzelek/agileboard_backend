<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\Company as ModelCompany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\InvoiceType;
use App\Models\Db\VatRate;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use Carbon\Carbon;
use App\Models\Other\InvoiceTypeStatus;
use Tests\BrowserKitTestCase;

class InvoiceRegistryReportTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function invoicesRegistryReport_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('/reports/invoices-registry-report?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function invoicesRegistry_validation_error_vat_rate_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $fake_vat_rate_id = $vat_rate->id;
        $vat_rate->delete();
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&vat_rate_id=' . $fake_vat_rate_id)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'vat_rate_id',
        ]);
    }

    /** @test */
    public function invoicesRegistry_validation_error_invoice_type_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create();
        $fake_invoice_type_id = $invoice_type->id;
        $invoice_type->delete();
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&invoice_type_id=' . $fake_invoice_type_id)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function invoicesRegistry_validation_error_invalid_month_and_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&month=' . 'no_integer' . '&year=' . 'no_integer')
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&month=' . 13 . '&year=' . 2051)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&month=' . 0 . '&year=' . 2000)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&month=' . 12)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['year']);
    }

    /** @test */
    public function reportInvoicesRegistry_response_structure_json()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 2)->create();
        $contractors = [];
        $invoice_tax_reports = [];
        foreach ($invoices as $invoice) {
            $contractors[] = factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $invoice->contractor_id,
            ]);
            $invoice_tax_reports[] = factory(InvoiceTaxReport::class)->create([
                'invoice_id' => $invoice->id,
            ]);
        }
        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    'vat_rates' => [
                        [
                        'vat_rate_id',
                        'vat_rate_name',
                        'price_net',
                        'vat_sum',
                        'price_gross',
                        ],
                    ],
                    'price_net',
                    'vat_sum',
                    'price_gross',
                ],
            ]);
    }

    /** @test */
    public function reportInvoicesRegistry_response_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 2)->create();
        $contractors = [];
        $day = 1;
        foreach ($invoices as $invoice) {
            $invoice->sale_date = Carbon::create(2017, 01, $day)->toDateString();
            $invoice->save();
            $contractors[] = factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $invoice->contractor_id,
            ]);
            ++$day;
        }

        $invoice_tax_reports = factory(InvoiceTaxReport::class, 4)->create();
        $vat_rates = factory(VatRate::class, 4)->create();
        $invoice_tax_reports[0]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'invoice_id' => $invoices[0]->id,
            'price_net' => 150,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[1]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'invoice_id' => $invoices[0]->id,
            'price_net' => -100,
            'price_gross' => -300,
        ]);
        $invoice_tax_reports[2]->update([
            'vat_rate_id' => $vat_rates[2]->id,
            'invoice_id' => $invoices[1]->id,
            'price_net' => 100,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[3]->update([
            'vat_rate_id' => $vat_rates[3]->id,
            'invoice_id' => $invoices[1]->id,
            'price_net' => 50,
            'price_gross' => -300,
        ]);

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
        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id)
            ->assertResponseOk();
        $json_data = $this->decodeResponseJson()['data']['vat_rates'];
        $index = 0;
        $this->assertSame(InvoiceTaxReport::count(), count($json_data));
        foreach ($invoice_tax_reports_expect as $expect) {
            $this->assertSame($expect['vat_sum'], $json_data[$index]['vat_sum']);
            $this->assertSame($expect['price_net'], $json_data[$index]['price_net']);
            $this->assertSame($expect['price_gross'], $json_data[$index]['price_gross']);
            ++$index;
        }
        $this->assertSame($invoice_tax_reports[0]->vat_rate_id, $json_data[0]['vat_rate_id']);
        $this->assertSame($invoice_tax_reports[0]->vatRate->name, $json_data[0]['vat_rate_name']);

        $this->assertSame($invoice_tax_reports[1]->vat_rate_id, $json_data[1]['vat_rate_id']);
        $this->assertSame($invoice_tax_reports[1]->vatRate->name, $json_data[1]['vat_rate_name']);

        $this->assertSame($invoice_tax_reports[2]->vat_rate_id, $json_data[2]['vat_rate_id']);
        $this->assertSame($invoice_tax_reports[2]->vatRate->name, $json_data[2]['vat_rate_name']);

        $this->assertSame($invoice_tax_reports[3]->vat_rate_id, $json_data[3]['vat_rate_id']);
        $this->assertSame($invoice_tax_reports[3]->vatRate->name, $json_data[3]['vat_rate_name']);

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(2, $json_data['price_net']);
        $this->assertSame(-2, $json_data['vat_sum']);
        $this->assertSame(0, $json_data['price_gross']);
    }

    /** @test */
    public function invoicesRegistry_filter_by_rate_rate_return_one_tax_report_from()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = factory(Invoice::class, 2)->create();

        $this->assignInvoiceToCompany($invoices, $company);

        $vat_rates = factory(VatRate::class, 4)->create();

        $invoice_tax_reports[$invoices[0]->id] = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoices[0]->id,
        ]);
        $invoice_tax_reports[$invoices[0]->id][0]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'price_net' => 100,
            'vat_sum' => 200,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[$invoices[0]->id][1]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'price_net' => 1000,
            'vat_sum' => 2000,
            'price_gross' => 3000,
        ]);

        $invoice_tax_reports[$invoices[1]->id] = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoices[1]->id,
        ]);
        $invoice_tax_reports[$invoices[1]->id][0]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'price_net' => 10000,
            'vat_sum' => 20000,
            'price_gross' => 30000,
        ]);
        $invoice_tax_reports[$invoices[1]->id][1]->update([
            'vat_rate_id' => $vat_rates[2]->id,
        ]);

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&vat_rate_id=' . $vat_rates[1]->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data']['vat_rates'];

        $this->assertSame(1, count($json_data));
        $this->assertSame(110, $json_data[0]['price_net']);
        $this->assertSame(220, $json_data[0]['vat_sum']);
        $this->assertSame(330, $json_data[0]['price_gross']);
        $this->assertSame($vat_rates[1]->id, $json_data[0]['vat_rate_id']);
        $this->assertSame($vat_rates[1]->name, $json_data[0]['vat_rate_name']);

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(110, $json_data['price_net']);
        $this->assertSame(220, $json_data['vat_sum']);
        $this->assertSame(330, $json_data['price_gross']);
    }

    /** @test */
    public function reportInvoicesRegistry_filter_by_invoice_type_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 2)->create();

        $invoices[0]->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id;
        $invoices[0]->save();

        $vat_rates = factory(VatRate::class, 2)->create();

        $invoice_tax_reports[$invoices[0]->id] = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoices[0]->id,
        ]);
        $invoice_tax_reports[$invoices[0]->id][0]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'price_net' => 100,
            'vat_sum' => 200,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[$invoices[0]->id][1]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'price_net' => 1000,
            'vat_sum' => 2000,
            'price_gross' => 3000,
        ]);

        $invoice_tax_reports[$invoices[1]->id] = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoices[1]->id,
        ]);
        $invoice_tax_reports[$invoices[1]->id][0]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'price_net' => 10000,
            'vat_sum' => 20000,
            'price_gross' => 30000,
        ]);
        $invoice_tax_reports[$invoices[1]->id][1]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'price_net' => 100000,
            'vat_sum' => 200000,
            'price_gross' => 300000,
        ]);

        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&invoice_type_id=' . InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data']['vat_rates'];

        $this->assertSame(2, count($json_data));
        $this->assertSame(1, $json_data[0]['price_net']);
        $this->assertSame(2, $json_data[0]['vat_sum']);
        $this->assertSame(3, $json_data[0]['price_gross']);
        $this->assertSame(10, $json_data[1]['price_net']);
        $this->assertSame(20, $json_data[1]['vat_sum']);
        $this->assertSame(30, $json_data[1]['price_gross']);
        $this->assertSame($vat_rates[0]->id, $json_data[0]['vat_rate_id']);
        $this->assertSame($vat_rates[0]->name, $json_data[0]['vat_rate_name']);
        $this->assertSame($vat_rates[1]->id, $json_data[1]['vat_rate_id']);
        $this->assertSame($vat_rates[1]->name, $json_data[1]['vat_rate_name']);

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(11, $json_data['price_net']);
        $this->assertSame(22, $json_data['vat_sum']);
        $this->assertSame(33, $json_data['price_gross']);
    }

    /** @test */
    public function reportInvoicesRegistry_filter_by_year_and_month()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = factory(Invoice::class, 2)->create();
        $invoices[0]->sale_date = Carbon::parse('2017-12-03')->toDateString();
        $invoices[0]->save();

        $invoices[1]->sale_date = Carbon::parse('2017-01-02')->toDateString();
        $invoices[1]->save();
        $this->assignInvoiceToCompany($invoices, $company);

        $vat_rates = factory(VatRate::class, 2)->create();

        $invoice_tax_reports[$invoices[0]->id] = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoices[0]->id,
        ]);
        $invoice_tax_reports[$invoices[0]->id]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'price_net' => 100,
            'vat_sum' => 200,
            'price_gross' => 300,
        ]);

        $invoice_tax_reports[$invoices[1]->id] = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoices[1]->id,
        ]);

        $invoice_tax_reports[$invoices[1]->id]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'price_net' => 100000,
            'vat_sum' => 200000,
            'price_gross' => 300000,
        ]);

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&year=' . 2017 . '&month=' . 1)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data']['vat_rates'];

        $this->assertSame(1, count($json_data));
        $this->assertSame(1000, $json_data[0]['price_net']);
        $this->assertSame(2000, $json_data[0]['vat_sum']);
        $this->assertSame(3000, $json_data[0]['price_gross']);
        $this->assertSame($vat_rates[1]->id, $json_data[0]['vat_rate_id']);
        $this->assertSame($vat_rates[1]->name, $json_data[0]['vat_rate_name']);

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1000, $json_data['price_net']);
        $this->assertSame(2000, $json_data['vat_sum']);
        $this->assertSame(3000, $json_data['price_gross']);
    }

    /** @test */
    public function reportInvoicesRegistry_skip_proforma()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 2)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoices[1]->update([
            'invoice_type_id' => $invoice_type->id,
        ]);
        $contractors = [];
        $day = 1;
        foreach ($invoices as $invoice) {
            $invoice->sale_date = Carbon::create(2017, 01, $day)->toDateString();
            $invoice->save();
            $contractors[] = factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $invoice->contractor_id,
            ]);
            ++$day;
        }

        $invoice_tax_reports = factory(InvoiceTaxReport::class, 4)->create();
        $vat_rates = factory(VatRate::class, 4)->create();
        $invoice_tax_reports[0]->update([
            'vat_rate_id' => $vat_rates[0]->id,
            'invoice_id' => $invoices[0]->id,
            'price_net' => 150,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[1]->update([
            'vat_rate_id' => $vat_rates[1]->id,
            'invoice_id' => $invoices[0]->id,
            'price_net' => -100,
            'price_gross' => -300,
        ]);

        $invoice_tax_reports[2]->update([
            'vat_rate_id' => $vat_rates[2]->id,
            'invoice_id' => $invoices[1]->id,
            'price_net' => 100,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[3]->update([
            'vat_rate_id' => $vat_rates[3]->id,
            'invoice_id' => $invoices[1]->id,
            'price_net' => 50,
            'price_gross' => -300,
        ]);

        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id)
            ->assertResponseOk();
        $json_data = $this->decodeResponseJson()['data']['vat_rates'];
        $this->assertSame(2, count($json_data));

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0.5, $json_data['price_net']);
        $this->assertSame(-0.5, $json_data['vat_sum']);
        $this->assertSame(0, $json_data['price_gross']);
    }

    /** @test */
    public function reportInvoicesRegistry_show_correction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->issueInvoice(InvoiceTypeStatus::VAT, $company, '2017-01-01');
        $this->issueInvoice(InvoiceTypeStatus::CORRECTION, $company, '2017-01-02');

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
        . '&month=' . 1 . '&year=' . 2017)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data']['vat_rates'];
        $this->assertSame(4, count($json_data));

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, $json_data['price_net']);
        $this->assertSame(-1, $json_data['vat_sum']);
        $this->assertSame(0, $json_data['price_gross']);
    }

    /** @test */
    public function reportInvoiceRegistry_show_tax_of_all_invoice()
    {
        $this->withoutExceptionHandling();
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->issueInvoice(InvoiceTypeStatus::MARGIN, $company, '2017-01-01');
        $this->issueInvoice(InvoiceTypeStatus::REVERSE_CHARGE, $company, '2017-01-01');
        $this->issueInvoice(InvoiceTypeStatus::ADVANCE, $company, '2017-01-01');
        $this->issueInvoice(InvoiceTypeStatus::MARGIN_CORRECTION, $company, '2017-01-02');
        $this->issueInvoice(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION, $company, '2017-01-02');
        $this->issueInvoice(InvoiceTypeStatus::ADVANCE_CORRECTION, $company, '2017-01-02');

        $this->get('reports/invoices-registry-report?selected_company_id=' . $company->id
            . '&month=' . 1 . '&year=' . 2017)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data']['vat_rates'];
        $this->assertSame(12, count($json_data));

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(3, $json_data['price_net']);
        $this->assertSame(-3, $json_data['vat_sum']);
        $this->assertSame(0, $json_data['price_gross']);
    }

    public function issueInvoice($slug, ModelCompany $company, $sale_date = null, $issue_date = null)
    {
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug($slug)->id,
            'sale_date' => $sale_date,
            'issue_date' => $issue_date ?? $sale_date,
        ]);

        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $invoice->contractor_id,
        ]);

        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create();

        $invoice_tax_reports[0]->update([
            'invoice_id' => $invoice->id,
            'price_net' => 150,
            'price_gross' => 300,
        ]);
        $invoice_tax_reports[1]->update([
            'invoice_id' => $invoice->id,
            'price_net' => -100,
            'price_gross' => -300,
        ]);

        return $invoice;
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
}
