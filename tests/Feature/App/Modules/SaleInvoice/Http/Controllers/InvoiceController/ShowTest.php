<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\ContractorAddress;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\InvoiceFinalAdvanceTaxReport;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceType;
use App\Models\Db\ServiceUnit;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\Receipt as ModelReceipt;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use App\Models\Db\InvoiceItem as ModelInvoiceItem;
use App\Models\Db\InvoiceReceipt as ModelInvoiceReceipt;
use App\Models\Db\InvoiceOnlineSale as ModelInvoiceOnlineSale;
use App\Models\Db\Contractor as ModelContractor;
use App\Models\Db\InvoiceInvoice as ModelInvoiceInvoice;
use App\Models\Db\InvoiceCompany as ModelInvoiceCompany;
use App\Models\Db\InvoiceContractor as ModelInvoiceContractor;
use App\Models\Db\InvoiceTaxReport as ModelInvoiceTaxReport;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;

class ShowTest extends FinancialEnvironment
{
    use DatabaseTransactions;

    /** @test */
    public function show_data_structure()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items, $online_sale, $invoice_taxes, $invoice_company, $invoice_contractor, $invoice_delivery_address) = $this->setInvoiceForAttributes();

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'order_number',
                    'invoice_registry_id',
                    'drawer_id',
                    'company_id',
                    'contractor_id',
                    'delivery_address_id',
                    'default_delivery',
                    'sale_date',
                    'issue_date',
                    'invoice_type_id',
                    'invoice_margin_procedure_id',
                    'invoice_reverse_charge_id',
                    'proforma_id',
                    'price_net',
                    'price_gross',
                    'vat_sum',
                    'payment_left',
                    'payment_term_days',
                    'payment_method_id',
                    'paid_at',
                    'gross_counted',
                    'description',
                    'last_printed_at',
                    'last_send_at',
                    'created_at',
                    'updated_at',
                    'is_editable',
                    'items' => [
                        'data' => [
                            [
                                'id',
                                'invoice_id',
                                'company_service_id',
                                'pkwiu',
                                'name',
                                'custom_name',
                                'price_net',
                                'price_net_sum',
                                'price_gross',
                                'price_gross_sum',
                                'vat_rate',
                                'vat_rate_id',
                                'vat_sum',
                                'quantity',
                                'service_unit_id',
                                'is_correction',
                                'position_corrected_id',
                                'proforma_item_id',
                                'creator_id',
                                'editor_id',
                                'created_at',
                                'updated_at',
                                'service_unit' => [
                                    'data' => [
                                        'id',
                                        'slug',
                                        'name',
                                        'created_at',
                                        'updated_at',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'taxes' => [
                        'data' => [
                            [
                                'id',
                                'invoice_id',
                                'vat_rate_id',
                                'price_net',
                                'price_gross',
                                'created_at',
                                'updated_at',
                            ],
                        ],
                    ],
                    'invoice_company' => [
                        'data' => [
                            'id',
                            'invoice_id',
                            'company_id',
                            'name',
                            'vatin',
                            'email',
                            'phone',
                            'bank_name',
                            'bank_account_number',
                            'main_address_street',
                            'main_address_number',
                            'main_address_zip_code',
                            'main_address_city',
                            'main_address_country',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'invoice_contractor' => [
                        'data' => [
                            'id',
                            'invoice_id',
                            'contractor_id',
                            'name',
                            'vatin',
                            'email',
                            'phone',
                            'bank_name',
                            'bank_account_number',
                            'main_address_street',
                            'main_address_number',
                            'main_address_zip_code',
                            'main_address_city',
                            'main_address_country',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'receipts' => [
                        'data' => [
                            [
                                'id',
                                'number',
                            ],
                        ],
                    ],
                    'online_sales' => [
                        'data' => [
                            [
                                'id',
                                'number',
                            ],
                        ],
                    ],
                    'invoices' => [
                        'data' => [
                            [
                                'id',
                                'number',
                            ],
                        ],
                    ],
                    'delivery_address' => [
                        'data' => [
                            'id',
                            'contractor_id',
                            'name',
                            'type',
                            'street',
                            'number',
                            'zip_code',
                            'city',
                            'country',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'invoice_delivery_address' => [
                        'data' => [
                            'id',
                            'invoice_id',
                            'street',
                            'number',
                            'zip_code',
                            'city',
                            'country',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'bank_account' => [
                        'data' => [
                            'id',
                            'number',
                            'bank_name',
                            'default',
                        ],
                    ],
                ],
            ])->isJson();
    }

    /** @test */
    public function show_with_correct_data_for_collective_invoice_receipts()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        ModelInvoice::whereRaw('1 = 1')->delete();
        $contractor = factory(ModelContractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(ModelInvoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'drawer_id' => $this->user->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
        ]);
        $receipts = factory(ModelReceipt::class, 3)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
        ]);
        $receipts->each(function ($receipt) use ($invoice) {
            factory(ModelInvoiceReceipt::class)->create([
                'invoice_id' => $invoice->id,
                'receipt_id' => $receipt->id,
            ]);
            factory(ModelInvoiceItem::class)->create([
                'invoice_id' => $invoice->id,
                'price_net' => 8227,
                'price_net_sum' => 4478,
                'price_gross' => 9579,
                'price_gross_sum' => 8754,
                'vat_sum' => 157,
                'custom_name' => 'custom_name',
                'base_document_id' => $receipt->id,
                'service_unit_id' => ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id,
            ]);
        });

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson();
        $data = $response['data'];

        // Receipts
        $receipts->each(function ($receipt, $key) use ($data) {
            $this->assertEquals($receipt->id, $data['receipts']['data'][$key]['id']);
            $this->assertEquals($receipt->number, $data['receipts']['data'][$key]['number']);
        });

        // Invoice items
        $receipts->each(function ($receipt, $key) use ($data) {
            $this->assertEquals($receipt->id, $data['items']['data'][$key]['base_document_id']);
            $this->assertEquals(
                'kilogram',
                $data['items']['data'][$key]['service_unit']['data']['name']
            );
        });
    }

    /** @test */
    public function show_with_correct_data_for_collective_invoice_online_sale()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        ModelInvoice::whereRaw('1 = 1')->delete();
        $contractor = factory(ModelContractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(ModelInvoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'drawer_id' => $this->user->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
        ]);

        $online_sales = factory(ModelOnlineSale::class, 3)->create([
            'company_id' => $company->id,
        ]);
        $online_sales->each(function ($online_sale) use ($invoice) {
            factory(ModelInvoiceOnlineSale::class)->create([
                'invoice_id' => $invoice->id,
                'online_sale_id' => $online_sale->id,
            ]);
            factory(ModelInvoiceItem::class)->create([
                'invoice_id' => $invoice->id,
                'price_net' => 8227,
                'price_net_sum' => 4478,
                'price_gross' => 9579,
                'price_gross_sum' => 8754,
                'vat_sum' => 157,
                'custom_name' => 'custom_name',
                'base_document_id' => $online_sale->id,
            ]);
        });

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson();
        $data = $response['data'];

        // Online sales
        $online_sales->each(function ($online_sale, $key) use ($data) {
            $this->assertEquals($online_sale->id, $data['online_sales']['data'][$key]['id']);
            $this->assertEquals($online_sale->number, $data['online_sales']['data'][$key]['number']);
        });

        // Invoice items
        $online_sales->each(function ($online_sale, $key) use ($data) {
            $this->assertEquals($online_sale->id, $data['items']['data'][$key]['base_document_id']);
        });
    }

    /** @test */
    public function show_with_correct_data()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items, $online_sale, $invoice_taxes, $invoice_company, $invoice_contractor, $invoice_delivery_address) = $this->setInvoiceForAttributes();

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->decodeResponseJson();
        $data = $response['data'];

        // Invoice
        $this->assertEquals($invoice->id, $data['id']);
        $this->assertEquals($invoice->number, $data['number']);
        $this->assertEquals($invoice->order_number, $data['order_number']);
        $this->assertEquals($invoice->invoice_registry_id, $data['invoice_registry_id']);
        $this->assertEquals($invoice->drawer_id, $data['drawer_id']);
        $this->assertEquals($invoice->company_id, $data['company_id']);
        $this->assertEquals($invoice->contractor_id, $data['contractor_id']);
        $this->assertEquals($invoice->delivery_address_id, $data['delivery_address_id']);
        $this->assertTrue((bool) $data['default_delivery']);
        $this->assertEquals($invoice->sale_date, $data['sale_date']);
        $this->assertEquals($invoice->issue_date, $data['issue_date']);
        $this->assertEquals($invoice->invoice_type_id, $data['invoice_type_id']);
        $this->assertEquals(54.25, $data['price_net']);
        $this->assertEquals(64.18, $data['price_gross']);
        $this->assertEquals(7.54, $data['vat_sum']);
        $this->assertEquals(48.54, $data['payment_left']);
        $this->assertEquals($invoice->payment_term_days, $data['payment_term_days']);
        $this->assertEquals($invoice->payment_method_id, $data['payment_method_id']);
        $this->assertEquals($invoice->paid_at, $data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $data['gross_counted']);
        $this->assertEquals($invoice->description, $data['description']);
        $this->assertEquals($invoice->last_printed_at, $data['last_printed_at']);
        $this->assertEquals($invoice->last_send_at, $data['last_send_at']);
        $this->assertEquals($invoice->last_send_at, $data['last_send_at']);
        $this->assertEquals($invoice->created_at, $data['created_at']);
        $this->assertSame($invoice->correction_type, $data['correction_type']);
        $this->assertSame($invoice->invoice_margin_procedure_id, $data['invoice_margin_procedure_id']);
        $this->assertSame($invoice->invoice_reverse_charge_id, $data['invoice_reverse_charge_id']);
        $this->assertSame($invoice->proforma_id, $data['proforma_id']);

        // Items
        $this->assertEquals($invoice_items[0]->id, $data['items']['data'][0]['id']);
        $this->assertEquals($invoice_items[0]->invoice_id, $data['items']['data'][0]['invoice_id']);
        $this->assertEquals($invoice_items[0]->company_service_id, $data['items']['data'][0]['company_service_id']);
        $this->assertEquals($invoice_items[0]->name, $data['items']['data'][0]['name']);
        $this->assertEquals($invoice_items[0]->custom_name, $data['items']['data'][0]['custom_name']);
        $this->assertEquals(82.27, $data['items']['data'][0]['price_net']);
        $this->assertEquals(44.78, $data['items']['data'][0]['price_net_sum']);
        $this->assertEquals(95.79, $data['items']['data'][0]['price_gross']);
        $this->assertEquals(87.54, $data['items']['data'][0]['price_gross_sum']);
        $this->assertEquals($invoice_items[0]->vat_rate, $data['items']['data'][0]['vat_rate']);
        $this->assertEquals($invoice_items[0]->vat_rate_id, $data['items']['data'][0]['vat_rate_id']);
        $this->assertEquals(1.57, $data['items']['data'][0]['vat_sum']);
        $this->assertEquals(12.345, $data['items']['data'][0]['quantity']);
        $this->assertEquals($invoice_items[0]->base_document_id, $data['items']['data'][0]['base_document_id']);
        $this->assertEquals($invoice_items[0]->is_correction, $data['items']['data'][0]['is_correction']);
        $this->assertEquals($invoice_items[0]->position_corrected_id, $data['items']['data'][0]['position_corrected_id']);
        $this->assertEquals($invoice_items[0]->proforma_item_id, $data['items']['data'][0]['proforma_item_id']);
        $this->assertEquals($invoice_items[0]->creator_id, $data['items']['data'][0]['creator_id']);
        $this->assertEquals($invoice_items[0]->editor_id, $data['items']['data'][0]['editor_id']);
        $this->assertEquals($invoice_items[0]->created_at, $data['items']['data'][0]['created_at']);
        $this->assertEquals($invoice_items[0]->updated_at, $data['items']['data'][0]['updated_at']);

        // Taxes
        $this->assertEquals($invoice_taxes[0]->id, $data['taxes']['data'][0]['id']);
        $this->assertEquals($invoice_taxes[0]->invoice_id, $data['taxes']['data'][0]['invoice_id']);
        $this->assertEquals($invoice_taxes[0]->vat_rate_id, $data['taxes']['data'][0]['vat_rate_id']);
        $this->assertEquals(99.99, $data['taxes']['data'][0]['price_net']);
        $this->assertEquals(88.88, $data['taxes']['data'][0]['price_gross']);
        $this->assertEquals($invoice_taxes[0]->created_at, $data['taxes']['data'][0]['created_at']);
        $this->assertEquals($invoice_taxes[0]->updated_at, $data['taxes']['data'][0]['updated_at']);

        // Invoice company
        $this->assertEquals($invoice_company->id, $data['invoice_company']['data']['id']);
        $this->assertEquals($invoice_company->invoice_id, $data['invoice_company']['data']['invoice_id']);
        $this->assertEquals($invoice_company->company_id, $data['invoice_company']['data']['company_id']);
        $this->assertEquals($invoice_company->name, $data['invoice_company']['data']['name']);
        $this->assertEquals($invoice_company->vatin, $data['invoice_company']['data']['vatin']);
        $this->assertEquals($invoice_company->email, $data['invoice_company']['data']['email']);
        $this->assertEquals($invoice_company->phone, $data['invoice_company']['data']['phone']);
        $this->assertEquals($invoice_company->bank_name, $data['invoice_company']['data']['bank_name']);
        $this->assertEquals($invoice_company->bank_account_number, $data['invoice_company']['data']['bank_account_number']);
        $this->assertEquals($invoice_company->main_address_street, $data['invoice_company']['data']['main_address_street']);
        $this->assertEquals($invoice_company->main_address_number, $data['invoice_company']['data']['main_address_number']);
        $this->assertEquals($invoice_company->main_address_zip_code, $data['invoice_company']['data']['main_address_zip_code']);
        $this->assertEquals($invoice_company->main_address_city, $data['invoice_company']['data']['main_address_city']);
        $this->assertEquals($invoice_company->main_address_country, $data['invoice_company']['data']['main_address_country']);
        $this->assertEquals($invoice_company->created_at, $data['invoice_company']['data']['created_at']);
        $this->assertEquals($invoice_company->updated_at, $data['invoice_company']['data']['updated_at']);
        $this->assertEquals('Polska', $data['invoice_company']['data']['vatin_prefix']['data']['name']);
        $this->assertEquals('PL', $data['invoice_company']['data']['vatin_prefix']['data']['key']);

        // Invoice contractor with vatin prefix
        $this->assertEquals($invoice_contractor->id, $data['invoice_contractor']['data']['id']);
        $this->assertEquals($invoice_contractor->invoice_id, $data['invoice_contractor']['data']['invoice_id']);
        $this->assertEquals($invoice_contractor->contractor_id, $data['invoice_contractor']['data']['contractor_id']);
        $this->assertEquals($invoice_contractor->name, $data['invoice_contractor']['data']['name']);
        $this->assertEquals($invoice_contractor->vatin, $data['invoice_contractor']['data']['vatin']);
        $this->assertEquals($invoice_contractor->email, $data['invoice_contractor']['data']['email']);
        $this->assertEquals($invoice_contractor->phone, $data['invoice_contractor']['data']['phone']);
        $this->assertEquals($invoice_contractor->bank_name, $data['invoice_contractor']['data']['bank_name']);
        $this->assertEquals($invoice_contractor->bank_account_number, $data['invoice_contractor']['data']['bank_account_number']);
        $this->assertEquals($invoice_contractor->main_address_street, $data['invoice_contractor']['data']['main_address_street']);
        $this->assertEquals($invoice_contractor->main_address_number, $data['invoice_contractor']['data']['main_address_number']);
        $this->assertEquals($invoice_contractor->main_address_zip_code, $data['invoice_contractor']['data']['main_address_zip_code']);
        $this->assertEquals($invoice_contractor->main_address_city, $data['invoice_contractor']['data']['main_address_city']);
        $this->assertEquals($invoice_contractor->main_address_country, $data['invoice_contractor']['data']['main_address_country']);
        $this->assertEquals($invoice_contractor->created_at, $data['invoice_contractor']['data']['created_at']);
        $this->assertEquals($invoice_contractor->updated_at, $data['invoice_contractor']['data']['updated_at']);
        $this->assertEquals('Polska', $data['invoice_contractor']['data']['vatin_prefix']['name']);
        $this->assertEquals('PL', $data['invoice_contractor']['data']['vatin_prefix']['key']);

        //Invoice delivery address
        $this->assertEquals($invoice_delivery_address->invoice_id, $data['invoice_delivery_address']['data']['invoice_id']);
        $this->assertEquals($invoice_delivery_address->street, $data['invoice_delivery_address']['data']['street']);
        $this->assertEquals($invoice_delivery_address->number, $data['invoice_delivery_address']['data']['number']);
        $this->assertEquals($invoice_delivery_address->zip_code, $data['invoice_delivery_address']['data']['zip_code']);
        $this->assertEquals($invoice_delivery_address->city, $data['invoice_delivery_address']['data']['city']);
        $this->assertEquals($invoice_delivery_address->country, $data['invoice_delivery_address']['data']['country']);

        // Online sales
        $this->assertEquals($online_sale->id, $data['online_sales']['data'][0]['id']);
        $this->assertEquals($online_sale->number, $data['online_sales']['data'][0]['number']);

        // Receipts
        $this->assertEquals($receipt->id, $data['receipts']['data'][0]['id']);
        $this->assertEquals($receipt->number, $data['receipts']['data'][0]['number']);

        // Invoices
        $this->assertEquals($invoice_node->id, $data['invoices']['data'][0]['id']);
        $this->assertEquals($invoice_node->number, $data['invoices']['data'][0]['number']);

        $this->assertArrayNotHasKey('order_number_date', $data);

        // Bank Account
        $bank_account = $company->bankAccounts()->first();
        $this->assertEquals($bank_account->id, $data['bank_account']['data']['id']);
        $this->assertEquals($bank_account->number, $data['bank_account']['data']['number']);
        $this->assertEquals($bank_account->bank_name, $data['bank_account']['data']['bank_name']);
        $this->assertEquals($bank_account->default, $data['bank_account']['data']['default']);
    }

    /** @test */
    public function show_with_invalid_invoice_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        ModelInvoice::whereRaw('1 = 1')->delete();
        factory(ModelInvoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_other_company = factory(ModelInvoice::class)->create([
            'company_id' => ($company->id + 5),
        ]);

        $this->get('/invoices/' . $invoice_other_company->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(404);
    }

    /** @test */
    public function show_with_partial_payment()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        ModelInvoice::whereRaw('1 = 1')->delete();
        $contractor = factory(ModelContractor::class)->create([
            'company_id' => $company->id,
        ]);
        $contractor_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $invoice = factory(ModelInvoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'delivery_address_id' => $contractor_address->id,
            'default_delivery' => 1,
            'drawer_id' => $this->user->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
            'invoice_margin_procedure_id' => InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id,
        ]);
        $invoice_node = factory(ModelInvoice::class)->create([
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'drawer_id' => $this->user->id,
            'price_net' => 8520,
            'price_gross' => 9852,
            'vat_sum' => 475,
            'payment_left' => 852,
        ]);
        factory(ModelInvoiceInvoice::class)->create([
            'parent_id' => $invoice->id,
            'node_id' => $invoice_node->id,
        ]);
        $receipt = factory(ModelReceipt::class)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
        ]);
        $invoice_items = factory(ModelInvoiceItem::class, 3)->create([
            'invoice_id' => $invoice->id,
            'price_net' => 8227,
            'price_net_sum' => 4478,
            'price_gross' => 9579,
            'price_gross_sum' => 8754,
            'vat_sum' => 157,
            'custom_name' => 'custom_name',
        ]);
        factory(ModelInvoiceReceipt::class)->create([
            'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id,
        ]);
        $online_sale = factory(ModelOnlineSale::class)->create([
            'company_id' => $company->id,
        ]);
        factory(ModelInvoiceOnlineSale::class)->create([
            'invoice_id' => $invoice->id,
            'online_sale_id' => $online_sale->id,
        ]);
        $invoice_taxes = factory(ModelInvoiceTaxReport::class, 3)->create([
            'invoice_id' => $invoice->id,
            'price_net' => 9999,
            'price_gross' => 8888,
        ]);
        $invoice_company = factory(ModelInvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
        ]);
        $invoice_contractor = factory(ModelInvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
        ]);
        $invoice_delivery_address = factory(InvoiceDeliveryAddress::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $sp_payment_1 = factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
            'amount' => 500,
        ]);
        $sp_payment_2 = factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
            'amount' => 611,
        ]);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $response = $this->response->getData()->data;
        $special_payments = $response->special_payments->data;
        $this->assertCount(2, $special_payments);
        $this->assertEquals($sp_payment_1->id, $special_payments[0]->id);
        $this->assertEquals($invoice->id, $special_payments[0]->invoice_id);
        $this->assertEquals(1, $special_payments[0]->special_partial_payment);
        $this->assertEquals(5, $special_payments[0]->amount);
        $this->assertEquals($sp_payment_2->id, $special_payments[1]->id);
        $this->assertEquals($invoice->id, $special_payments[1]->invoice_id);
        $this->assertEquals(1, $special_payments[1]->special_partial_payment);
        $this->assertEquals(6.11, $special_payments[1]->amount);
    }

    /** @test */
    public function show_items_paid_null_for_no_proforma()
    {
        list($company, $invoice) = $this->setInvoiceForAttributes();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
        ]);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $invoice_items_response = $this->decodeResponseJson()['data']['items']['data'];
        $this->assertNull($invoice_items_response[0]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[0]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['gross']);
    }

    /** @test */
    public function show_items_paid_null_if_disable_advance_invoice_module()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items) = $this->setInvoiceForAttributes();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, false);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);
        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advance->id,
            'proforma_item_id' => $invoice_items[0]->id,
            'price_gross_sum' => 100,
        ]);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $invoice_items_response = $this->decodeResponseJson()['data']['items']['data'];
        $this->assertNull($invoice_items_response[0]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[0]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['gross']);
    }

    /** @test */
    public function show_proforma_with_advance_invoice_payment()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items) = $this->setInvoiceForAttributes();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advances = factory(ModelInvoice::class, 2)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);
        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advances[0]->id,
            'proforma_item_id' => $invoice_items[0]->id,
            'price_gross_sum' => 100,
            'price_net_sum' => 50,
        ]);

        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advances[1]->id,
            'proforma_item_id' => $invoice_items[0]->id,
            'price_gross_sum' => 200,
            'price_net_sum' => 100,
        ]);

        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advances[1]->id,
            'proforma_item_id' => $invoice_items[1]->id,
            'price_gross_sum' => 400,
            'price_net_sum' => 200,
         ]);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $invoice_items_response = $this->decodeResponseJson()['data']['items']['data'];
        $this->assertEquals(3, $invoice_items_response[0]['paid']['data']['gross']);
        $this->assertEquals(1.5, $invoice_items_response[0]['paid']['data']['net']);
        $this->assertEquals(4, $invoice_items_response[1]['paid']['data']['gross']);
        $this->assertEquals(2, $invoice_items_response[1]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['net']);
    }

    /** @test */
    public function show_proforma_paid_not_including_deleted_advance_invoice()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items) = $this->setInvoiceForAttributes();
        $this->setAppSettings($company, ModuleType::INVOICES_ADVANCE_ENABLED, true);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $invoice_advance = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
        ]);
        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advance->id,
            'proforma_item_id' => $invoice_items[0]->id,
            'price_gross_sum' => 100,
        ]);

        factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice_advance->id,
            'proforma_item_id' => $invoice_items[1]->id,
            'price_gross_sum' => 200,
        ]);
        $invoice_advance->delete();

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $invoice_items_response = $this->decodeResponseJson()['data']['items']['data'];
        $this->assertNull($invoice_items_response[0]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['net']);
        $this->assertNull($invoice_items_response[0]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[1]['paid']['data']['gross']);
        $this->assertNull($invoice_items_response[2]['paid']['data']['gross']);
    }

    /** @test */
    public function show_is_editable_invoice()
    {
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertTrue($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_is_not_editable_invoice_because_of_related_with_correction()
    {
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $correction_invoice = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION),
            'corrected_invoice_id' => $invoice->id,
        ]);
        $correction_invoice->nodeInvoices()->attach($invoice->id);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertFalse($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_is_not_editable_invoice_because_of_is_correction()
    {
        $this->withoutExceptionHandling();
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $corrected_invoice = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT),
        ]);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $corrected_invoice->id,
        ]);

        $invoice->nodeInvoices()->attach($corrected_invoice->id);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertFalse($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_is_not_editable_invoice_because_of_is_margin_correction()
    {
        $this->withoutExceptionHandling();
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $corrected_invoice = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN),
        ]);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN_CORRECTION)->id,
            'corrected_invoice_id' => $corrected_invoice->id,
        ]);

        $invoice->nodeInvoices()->attach($corrected_invoice->id);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertFalse($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_is_not_editable_invoice_because_of_related_with_advance()
    {
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $advance_invoice = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $invoice->id,
        ]);
        $advance_invoice->nodeInvoices()->attach($invoice->id);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertFalse($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_is_editable_advance_invoice()
    {
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $proforma = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $proforma->id,
        ]);
        $invoice->nodeInvoices()->attach($proforma->id);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $this->assertTrue($this->decodeResponseJson()['data']['is_editable']);
    }

    /** @test */
    public function show_include_final_advance_taxes()
    {
        list($company, $invoice, $invoice_node) = $this->setInvoiceForAttributes();
        $invoice_node->delete();

        $proforma = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::FINAL_ADVANCE)->id,
            'proforma_id' => $proforma->id,
        ]);
        $invoice->nodeInvoices()->attach($proforma->id);

        factory(InvoiceFinalAdvanceTaxReport::class)->create([
           'invoice_id' => $invoice->id,
           'price_net' => 9999,
           'price_gross' => 8888,
        ]);

        $this->get('/invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->seeStatusCode(200);

        $final_advance_tax = $this->decodeResponseJson()['data']['final_advance_taxes']['data'][0];
        $this->assertEquals($invoice->id, $final_advance_tax['invoice_id']);
        $this->assertEquals(99.99, $final_advance_tax['price_net']);
        $this->assertEquals(88.88, $final_advance_tax['price_gross']);
    }
}
