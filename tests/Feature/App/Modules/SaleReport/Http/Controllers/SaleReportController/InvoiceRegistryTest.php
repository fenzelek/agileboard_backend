<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Models\Db\Company as ModelCompany;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Db\VatRate;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use Tests\BrowserKitTestCase;

class InvoiceRegistryTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function invoicesRegistry_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->get('/reports/invoices-registry?selected_company_id=' . $company->id)
            ->assertResponseOk();
    }

    /** @test */
    public function invoicesRegistry_validation_error_vat_rate_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $vat_rate = factory(VatRate::class)->create();
        $fake_vat_rate_id = $vat_rate->id;
        $vat_rate->delete();
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
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
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&invoice_type_id=' . $fake_invoice_type_id)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function invoiceRegistry_validation_error_invoice_type_given_proforma()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&invoice_type_id=' . $invoice_type->id)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function invoicesRegistry_validation_error_invalid_month_and_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&month=' . 'no_integer' . '&year=' . 'no_integer')
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&month=' . 13 . '&year=' . 2051)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&month=' . 0 . '&year=' . 2000)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'month',
            'year',
        ]);

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&month=' . 12)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['year']);
    }

    /** @test */
    public function invoicesRegistry_response_structure_json()
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
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [
                    [
                        'id',
                        'number',
                        'company_id',
                        'contractor_id',
                        'sale_date',
                        'issue_date',
                        'invoice_type_id',
                        'name',
                        'vatin',
                        'main_address_street',
                        'main_address_number',
                        'main_address_zip_code',
                        'main_address_city',
                        'main_address_country',
                        'taxes' => [
                            'data' => [
                                [
                                    'id',
                                    'invoice_id',
                                    'vat_rate_id',
                                    'vat_rate_name',
                                    'price_net',
                                    'price_gross',
                                    'vat_sum',
                                ],

                            ],

                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function invoicesRegistry_response_correct_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 4)->create();
        $invoices[2]->deleted_at = '2017-05-11 13:00:00';
        $invoices[2]->save();
        $invoices[3]->deleted_at = '2017-05-11 13:00:00';
        $invoices[3]->save();

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

            // those below are data for deleted invoices - just in case
            $invoices[2]->id => [
                [
                    'price_net' => 1000,
                    'price_gross' => 3003,
                ],
                [
                    'price_net' => 500,
                    'price_gross' => -3000,
                ],
            ],
            $invoices[3]->id => [
                [
                    'price_net' => 2000,
                    'price_gross' => 600,
                ],
                [
                    'price_net' => 522,
                    'price_gross' => -1300,
                ],
            ],
        ];
        $day = 1;
        foreach ($invoices as $invoice) {
            $invoice->sale_date = Carbon::create(2017, 01, $day)->toDateString();
            $invoice->save();
            $contractors[] = factory(InvoiceContractor::class)->create([
                'invoice_id' => $invoice->id,
                'contractor_id' => $invoice->contractor_id,
            ]);
            $invoice_tax_reports[$invoice->id] = factory(InvoiceTaxReport::class, 2)->create([
                'invoice_id' => $invoice->id,
            ]);
            foreach ($invoice_tax_reports[$invoice->id] as $key => $item) {
                $item->price_net = $incoming_data[$invoice->id][$key]['price_net'];
                $item->price_gross = $incoming_data[$invoice->id][$key]['price_gross'];
                $item->save();
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
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $index = 0;
        $this->assertSame(2, count($json_data));
        foreach ($invoices->where('deleted_at', null) as $invoice) {
            $this->assertSame($invoice->id, $json_data[$index]['id']);
            $this->assertSame($invoice->number, $json_data[$index]['number']);
            $this->assertSame($invoice->company_id, $json_data[$index]['company_id']);
            $this->assertSame($invoice->contractor_id, $json_data[$index]['contractor_id']);
            $this->assertSame($invoice->sale_date, $json_data[$index]['sale_date']);
            $this->assertSame($invoice->issue_date, $json_data[$index]['issue_date']);
            $this->assertSame($invoice->invoice_type_id, $json_data[$index]['invoice_type_id']);
            $this->assertSame($contractors[$index]->name, $json_data[$index]['name']);
            $this->assertSame($contractors[$index]->vatin, $json_data[$index]['vatin']);
            $this->assertSame(
                $contractors[$index]->main_address_street,
                $json_data[$index]['main_address_street']
            );
            $this->assertEquals(
                $contractors[$index]->main_address_number,
                $json_data[$index]['main_address_number']
            );
            $this->assertSame(
                $contractors[$index]->main_address_zip_code,
                $json_data[$index]['main_address_zip_code']
            );
            $this->assertSame(
                $contractors[$index]->main_address_city,
                $json_data[$index]['main_address_city']
            );
            $this->assertSame(
                $contractors[$index]->main_address_country,
                $json_data[$index]['main_address_country']
            );
            $taxes = $json_data[$index]['taxes']['data'];
            $tax_index = 0;
            foreach ($invoice_tax_reports[$invoice->id] as $invoice_tax_report) {
                $this->assertSame(
                    InvoiceTaxReport::where('invoice_id', $invoice->id)->count(),
                    count($taxes)
                );
                $this->assertSame($invoice_tax_report->id, $taxes[$tax_index]['id']);
                $this->assertSame(
                    $invoice_tax_report->invoice_id,
                    $taxes[$tax_index]['invoice_id']
                );
                $this->assertSame(
                    $invoice_tax_report->vat_rate_id,
                    $taxes[$tax_index]['vat_rate_id']
                );
                $this->assertSame(
                    $invoice_tax_report->vatRate->name,
                    $taxes[$tax_index]['vat_rate_name']
                );
                $this->assertSame(
                    $invoice_tax_reports_expect[$invoice->id][$tax_index]['vat_sum'],
                    $taxes[$tax_index]['vat_sum']
                );
                $this->assertSame(
                    $invoice_tax_reports_expect[$invoice->id][$tax_index]['price_gross'],
                    $taxes[$tax_index]['price_gross']
                );
                $this->assertSame(
                    $invoice_tax_reports_expect[$invoice->id][$tax_index]['price_net'],
                    $taxes[$tax_index]['price_net']
                );

                ++$tax_index;
            }
            ++$index;
        }
    }

    /** @test */
    public function invoicesRegistry_skip_proforma()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = $this->createInvoicesAndAssignToCompany($company);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoices[1]->invoice_type_id = $invoice_type->id;
        $invoices[1]->save();

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id)
            ->assertResponseOk();
        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $this->assertSame($invoices[0]->id, $json_data[0]['id']);
        $this->assertSame($invoices[0]->number, $json_data[0]['number']);
        $this->assertSame($invoices[0]->company_id, $json_data[0]['company_id']);
        $this->assertSame($invoices[0]->contractor_id, $json_data[0]['contractor_id']);
        $this->assertSame($invoices[0]->sale_date, $json_data[0]['sale_date']);
        $this->assertSame($invoices[0]->issue_date, $json_data[0]['issue_date']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[0]['invoice_type_id']);
    }

    /** @test */
    public function invoicesRegistry_filter_by_invoice_type_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoices = factory(Invoice::class, 2)->create();

        $invoices[0]->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id;
        $invoices[0]->save();

        $contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoices[0]->id,
            'contractor_id' => $invoices[0]->contractor_id,
        ]);

        $this->assignInvoiceToCompany($invoices, $company);
        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&invoice_type_id=' . InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $index = 0;
        $this->assertSame(1, count($json_data));
        $this->assertSame($invoices[0]->id, $json_data[$index]['id']);
        $this->assertSame($invoices[0]->number, $json_data[$index]['number']);
        $this->assertSame($invoices[0]->company_id, $json_data[$index]['company_id']);
        $this->assertSame($invoices[0]->contractor_id, $json_data[$index]['contractor_id']);
        $this->assertSame($invoices[0]->sale_date, $json_data[$index]['sale_date']);
        $this->assertSame($invoices[0]->issue_date, $json_data[$index]['issue_date']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[$index]['invoice_type_id']);
    }

    /** @test */
    public function invoicesRegistry_filter_by_year()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = $this->createInvoicesAndAssignToCompany($company);

        $invoices[0]->sale_date = Carbon::parse('2016-12-03')->toDateString();
        $invoices[0]->save();

        $invoices[1]->sale_date = Carbon::parse('2017-01-02')->toDateString();
        $invoices[1]->save();

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2016)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $index = 0;
        $this->assertSame(1, count($json_data));
        $this->assertSame($invoices[0]->id, $json_data[$index]['id']);
        $this->assertSame($invoices[0]->number, $json_data[$index]['number']);
        $this->assertSame($invoices[0]->company_id, $json_data[$index]['company_id']);
        $this->assertSame($invoices[0]->contractor_id, $json_data[$index]['contractor_id']);
        $this->assertSame($invoices[0]->sale_date, $json_data[$index]['sale_date']);
        $this->assertSame($invoices[0]->issue_date, $json_data[$index]['issue_date']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[$index]['invoice_type_id']);

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2017)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $index = 0;
        $this->assertSame(1, count($json_data));
        $this->assertSame($invoices[1]->id, $json_data[$index]['id']);
        $this->assertSame($invoices[1]->number, $json_data[$index]['number']);
        $this->assertSame($invoices[1]->company_id, $json_data[$index]['company_id']);
        $this->assertSame($invoices[1]->contractor_id, $json_data[$index]['contractor_id']);
        $this->assertSame($invoices[1]->sale_date, $json_data[$index]['sale_date']);
        $this->assertSame($invoices[1]->issue_date, $json_data[$index]['issue_date']);
        $this->assertSame($invoices[1]->invoice_type_id, $json_data[$index]['invoice_type_id']);

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2018)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, count($json_data));
    }

    /** @test */
    public function invoicesRegistry_filter_by_year_and_month()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = $this->createInvoicesAndAssignToCompany($company);

        $invoices[0]->sale_date = Carbon::parse('2017-12-03')->toDateString();
        $invoices[0]->save();

        $invoices[1]->sale_date = Carbon::parse('2017-01-02')->toDateString();
        $invoices[1]->save();

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2017 . '&month=' . 5)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, count($json_data));

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2017 . '&month=' . 12)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $index = 0;
        $this->assertSame(1, count($json_data));
        $this->assertSame($invoices[0]->id, $json_data[$index]['id']);
        $this->assertSame($invoices[0]->number, $json_data[$index]['number']);
        $this->assertSame($invoices[0]->company_id, $json_data[$index]['company_id']);
        $this->assertSame($invoices[0]->contractor_id, $json_data[$index]['contractor_id']);
        $this->assertSame($invoices[0]->sale_date, $json_data[$index]['sale_date']);
        $this->assertSame($invoices[0]->issue_date, $json_data[$index]['issue_date']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[$index]['invoice_type_id']);

        $invoices[1]->sale_date = Carbon::parse('2017-12-02')->toDateString();
        $invoices[1]->save();

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&year=' . 2017 . '&month=' . 12)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(2, count($json_data));
    }

    /** @test */
    public function invoicesRegistry_filter_by_vat_rate()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = $this->createInvoicesAndAssignToCompany($company);

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

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&vat_rate_id=' . $vat_rates[3]->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(0, count($json_data));

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&vat_rate_id=' . $vat_rates[0]->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(1, count($json_data));
        $index = 0;
        $this->assertSame($invoices[0]->id, $json_data[$index]['id']);
        $this->assertSame($invoices[0]->number, $json_data[$index]['number']);
        $this->assertSame($invoices[0]->company_id, $json_data[$index]['company_id']);
        $this->assertSame($invoices[0]->contractor_id, $json_data[$index]['contractor_id']);
        $this->assertSame($invoices[0]->sale_date, $json_data[$index]['sale_date']);
        $this->assertSame($invoices[0]->issue_date, $json_data[$index]['issue_date']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[$index]['invoice_type_id']);
        $this->assertSame($invoices[0]->invoice_type_id, $json_data[$index]['invoice_type_id']);

        $taxes = $json_data[$index]['taxes']['data'];
        $this->assertSame(1, count($taxes));
        $tax_index = 0;

        $this->assertSame($invoice_tax_reports[$invoices[0]->id][0]->id, $taxes[$tax_index]['id']);
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][0]->invoice_id,
            $taxes[$tax_index]['invoice_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][0]->vat_rate_id,
            $taxes[$tax_index]['vat_rate_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][0]->vatRate->name,
            $taxes[$tax_index]['vat_rate_name']
        );
        $this->assertSame(1, $taxes[$tax_index]['price_net']);
        $this->assertSame(2, $taxes[$tax_index]['vat_sum']);
        $this->assertSame(3, $taxes[$tax_index]['price_gross']);
    }

    /** @test */
    public function invoicesRegistry_filter_by_rate_rate_return_one_tax_report_from()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoices = $this->createInvoicesAndAssignToCompany($company);

        $invoices[0]->sale_date = Carbon::parse('2016-12-03')->toDateString();
        $invoices[0]->save();

        $invoices[1]->sale_date = Carbon::parse('2017-01-02')->toDateString();
        $invoices[1]->save();

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

        $this->get('reports/invoices-registry?selected_company_id=' . $company->id
            . '&vat_rate_id=' . $vat_rates[1]->id)
            ->assertResponseOk();

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame(2, count($json_data));

        for ($index = 0; $index < 1; $index++) {
            $this->assertSame($invoices[$index]->id, $json_data[$index]['id']);
            $this->assertSame($invoices[$index]->number, $json_data[$index]['number']);
            $this->assertSame($invoices[$index]->company_id, $json_data[$index]['company_id']);
            $this->assertSame(
                $invoices[$index]->contractor_id,
                $json_data[$index]['contractor_id']
            );
            $this->assertSame($invoices[$index]->sale_date, $json_data[$index]['sale_date']);
            $this->assertSame($invoices[$index]->issue_date, $json_data[$index]['issue_date']);
            $this->assertSame(
                $invoices[$index]->invoice_type_id,
                $json_data[$index]['invoice_type_id']
            );
            $this->assertSame(
                $invoices[$index]->invoice_type_id,
                $json_data[$index]['invoice_type_id']
            );
        }

        $taxes = $json_data[0]['taxes']['data'];
        $this->assertSame(1, count($taxes));
        $tax_index = 0;
        $this->assertSame($invoice_tax_reports[$invoices[0]->id][1]->id, $taxes[$tax_index]['id']);
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][1]->invoice_id,
            $taxes[$tax_index]['invoice_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][1]->vat_rate_id,
            $taxes[$tax_index]['vat_rate_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[0]->id][1]->vatRate->name,
            $taxes[$tax_index]['vat_rate_name']
        );
        $this->assertSame(10, $taxes[$tax_index]['price_net']);
        $this->assertSame(20, $taxes[$tax_index]['vat_sum']);
        $this->assertSame(30, $taxes[$tax_index]['price_gross']);

        $taxes = $json_data[1]['taxes']['data'];
        $this->assertSame(1, count($taxes));
        $this->assertSame($invoice_tax_reports[$invoices[1]->id][0]->id, $taxes[$tax_index]['id']);
        $this->assertSame(
            $invoice_tax_reports[$invoices[1]->id][0]->invoice_id,
            $taxes[$tax_index]['invoice_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[1]->id][0]->vat_rate_id,
            $taxes[$tax_index]['vat_rate_id']
        );
        $this->assertSame(
            $invoice_tax_reports[$invoices[1]->id][0]->vatRate->name,
            $taxes[$tax_index]['vat_rate_name']
        );
        $this->assertSame(100, $taxes[$tax_index]['price_net']);
        $this->assertSame(200, $taxes[$tax_index]['vat_sum']);
        $this->assertSame(300, $taxes[$tax_index]['price_gross']);
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

        return $invoices->sortBy('sale_date')->sortBy('id');
    }
}
