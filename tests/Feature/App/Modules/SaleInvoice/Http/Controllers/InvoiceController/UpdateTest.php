<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\BankAccount;
use App\Models\Db\ContractorAddress;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\InvoiceType;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use Carbon\Carbon;
use App\Models\Db\CashFlow;
use App\Models\Db\InvoiceOnlineSale;
use App\Models\Db\InvoiceReceipt;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\OnlineSale;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceItem;
use App\Models\Db\Receipt;
use App\Models\Db\Contractor;
use App\Models\Db\PaymentMethod;
use App\Models\Db\CompanyService;
use App\Models\Db\VatRate;
use App\Models\Db\Company;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoicePayment;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment;

class UpdateTest extends BrowserKitTestCase
{
    use DatabaseTransactions, FinancialEnvironment;

    /** @test */
    public function update_data_check_structure_response()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
            'print_on_invoice' => false,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
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
                    'corrected_invoice_id',
                    'correction_type',
                    'invoice_margin_procedure_id',
                    'sale_date',
                    'issue_date',
                    'invoice_type_id',
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
                ],
            ])->isJson();
    }

    /** @test */
    public function update_it_returns_validation_error_with_invalid_inputs()
    {
        Company::whereRaw('1 = 1')->delete();
        Contractor::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => ($contractor->id + 100),
            'issue_date' => '20172',
            'sale_date' => '2179',
            'paid_at' => '2019',
            'price_net' => 'test',
            'price_gross' => 'abc',
            'vat_sum' => 'test1',
            'gross_counted' => 8,
            'description' => Factory::create()->words(1000),
            'payment_term_days' => 5854,
            'payment_method_id' => ($payment_method->id + 123),
            'items' => 'items',
            'taxes' => 'taxes',
        ])
            ->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'contractor_id',
                'sale_date',
                'issue_date',
                'price_net',
                'price_gross',
                'vat_sum',
                'gross_counted',
                'description',
                'payment_term_days',
                'payment_method_id',
                'items',
                'taxes',
            ]
        );
    }

    /** @test */
    public function update_company_has_disabled_delivery_address_on_invoices()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, false);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address->id,
        ]);
        $this->assertSame(0, InvoiceDeliveryAddress::count());
        $invoice = Invoice::latest()->first();
        $this->assertNull($invoice->delivery_adddress_id);
        $this->assertTrue((bool) $invoice->default_delivery);
    }

    /** @test */
    public function update_company_has_enabled_delivery_address_validation_error()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'delivery_address_id',
            'default_delivery',
        ]);
    }

    /** @test */
    public function update_bank_account_validation_error()
    {
        $other_company = factory(Company::class)->create();
        $other_company->bankAccounts()->save(factory(BankAccount::class)->make(['default' => true]));

        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'bank_account_id' => $other_company->defaultBankAccount()->id,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'bank_account_id',
        ]);
    }

    /** @test */
    public function update_company_has_enabled_delivery_address_validation_error_address_not_belongs_to_contractor()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $fake_contractor = factory(Contractor::class)->create();
        $delivery_address_id = $fake_contractor->id;
        $fake_contractor->delete();
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address_id,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'delivery_address_id',
            'default_delivery',
        ]);
    }

    /** @test */
    public function update_address_contractor_from_other_company_validation_error()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $other_company = factory(Company::class)->create();
        $other_contractor = factory(Contractor::class)->create([
            'company_id' => $other_company->id,
        ]);
        $delivery_address = ContractorAddress::create([
            'contractor_id' => $other_contractor->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address->id,
            'default_delivery' => 'not_valid_boolean',
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'delivery_address_id',
            'default_delivery',
        ]);
    }

    /** @test */
    public function update_add_delivery_address_to_database()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $delivery_address = factory(ContractorAddress::class)->create([
           'contractor_id' => $contractor->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address->id,
            'default_delivery' => 0,
        ])->assertResponseStatus(200);
        $this->assertSame($initial_delivery_addresses + 1, InvoiceDeliveryAddress::count());
        $invoice = Invoice::latest()->first();
        $this->assertFalse((bool) $invoice->default_delivery);
        $invoice_delivery_address = InvoiceDeliveryAddress::latest()->first();
        $this->assertSame($delivery_address->id, $invoice->delivery_address_id);
        $this->assertSame($invoice->id, $invoice_delivery_address->invoice_id);
        $this->assertSame($delivery_address->street, $invoice_delivery_address->street);
        $this->assertEquals($delivery_address->number, $invoice_delivery_address->number);
        $this->assertEquals($delivery_address->zip_code, $invoice_delivery_address->zip_code);
        $this->assertSame($delivery_address->city, $invoice_delivery_address->city);
        $this->assertSame($delivery_address->country, $invoice_delivery_address->country);
        $this->assertSame($delivery_address->contractor_id, $invoice_delivery_address->receiver_id);
        $this->assertSame($delivery_address->contractor->name, $invoice_delivery_address->receiver_name);
    }

    /** @test */
    public function update_replace_old_delivery_address_in_database()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        factory(InvoiceDeliveryAddress::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address->id,
            'default_delivery' => 1,
        ])->assertResponseStatus(200);
        $this->assertSame($initial_delivery_addresses, InvoiceDeliveryAddress::count());
        $invoice_delivery_address = InvoiceDeliveryAddress::latest()->first();
        $invoice = $invoice->fresh();
        $this->assertSame($delivery_address->id, $invoice->delivery_address_id);
        $this->assertSame($invoice->id, $invoice_delivery_address->invoice_id);
        $this->assertSame($delivery_address->street, $invoice_delivery_address->street);
        $this->assertEquals($delivery_address->number, $invoice_delivery_address->number);
        $this->assertEquals($delivery_address->zip_code, $invoice_delivery_address->zip_code);
        $this->assertSame($delivery_address->city, $invoice_delivery_address->city);
        $this->assertSame($delivery_address->country, $invoice_delivery_address->country);
        $this->assertSame($delivery_address->contractor_id, $invoice_delivery_address->receiver_id);
        $this->assertSame($delivery_address->contractor->name, $invoice_delivery_address->receiver_name);
    }

    /** @test */
    public function update_not_remove_delivery_address_if_module_disabled()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $invoice_delivery_address = factory(InvoiceDeliveryAddress::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice->delivery_address_id = $invoice_delivery_address->id;
        $invoice->save();
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, false);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'delivery_address_id' => $delivery_address->id,
            'default_delivery' => 0,
        ])->assertResponseStatus(200);
        $this->assertSame($initial_delivery_addresses, InvoiceDeliveryAddress::count());
        $invoice = $invoice->fresh();
        $this->assertTrue((bool) $invoice->default_delivery);
        $recent_delivery_address = $invoice_delivery_address->fresh();
        $this->assertSame($recent_delivery_address->id, $invoice->delivery_address_id);
        $this->assertSame($invoice->id, $recent_delivery_address->invoice_id);
        $this->assertSame($invoice_delivery_address->street, $recent_delivery_address->street);
        $this->assertSame($invoice_delivery_address->city, $recent_delivery_address->city);
        $this->assertSame($invoice_delivery_address->country, $recent_delivery_address->country);
        $this->assertSame($invoice_delivery_address->receiver_id, $recent_delivery_address->receiver_id);
        $this->assertSame($invoice_delivery_address->receiver_name, $recent_delivery_address->receiver_name);
    }

    /** @test */
    public function update_proforma_disabled_module_error()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            false
        );
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoice->invoice_type_id = $invoice_type->id;
        $invoice->save();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'id',
        ]);
    }

    /** @test */
    public function update_proforma_block_by_advance_invoice()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoice->invoice_type_id = $invoice_type->id;
        $invoice->save();

        $advance_invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE),
            'proforma_id' => $invoice->id,
        ]);
        $advance_invoice->nodeInvoices()->attach($invoice->id);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_method_id' => $payment_method->id,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'id',
        ]);
    }

    /** @test */
    public function update_invoice_with_invoice_correction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoice = factory(Invoice::class)->create();
        factory(Invoice::class)->create([
            'corrected_invoice_id' => $invoice->id,
        ]);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(404);
    }

    /** @test */
    public function update_data_invoice_with_receipt()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        Invoice::whereRaw('1 = 1')->delete();
        InvoiceItem::whereRaw('1 = 1')->delete();
        InvoiceTaxReport::whereRaw('1 = 1')->delete();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,

        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
            'creator_id' => $this->user->id,
        ]);
        $invoice_tax_report = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->receipts()->attach($receipt);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $invoice_payments_count = InvoicePayment::count();
        $invoice_items_count = InvoiceItem::count();
        $invoice_tax_reports_count = InvoiceTaxReport::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09',
            'price_net' => 12.34,
            'price_gross' => 14.85,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame($invoice_items_count, InvoiceItem::count());
        $this->assertSame($invoice_tax_reports_count, InvoiceTaxReport::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->correction_type, $invoice_fresh->correction_type);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame($invoice->sale_date, $invoice_fresh->sale_date);
        $this->assertSame($invoice->paid_at, $invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame($invoice->price_net, $invoice_fresh->price_net);
        $this->assertSame($invoice->price_gross, $invoice_fresh->price_gross);
        $this->assertSame($invoice->vat_sum, $invoice_fresh->vat_sum);
        $this->assertSame($invoice->payment_left, $invoice_fresh->payment_left);
        $this->assertSame($invoice->payment_term_days, $invoice_fresh->payment_term_days);
        $this->assertSame($invoice->payment_method_id, $invoice_fresh->payment_method_id);
        $this->assertNull($invoice->bank_account_id);
        $this->assertSame($invoice->paid_at, $invoice_fresh->paid_at);
        $this->assertSame($invoice->gross_counted, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertNull($invoice_company_fresh->bank_name);
        $this->assertNull($invoice_company_fresh->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        $this->assertSame($invoice->id, $invoice_items_fresh[0]->invoice_id);
        $this->assertSame($invoice_item->company_service_id, $invoice_items_fresh[0]->company_service_id);
        $this->assertSame($invoice_item->name, $invoice_items_fresh[0]->name);
        $this->assertSame($invoice_item->custom_name, $invoice_items_fresh[0]->custom_name);
        $this->assertSame($invoice_item->price_net_sum, $invoice_items_fresh[0]->price_net_sum);
        $this->assertSame($invoice_item->price_gross_sum, $invoice_items_fresh[0]->price_gross_sum);
        $this->assertSame($invoice_item->vat_sum, $invoice_items_fresh[0]->vat_sum);
        $this->assertSame($invoice_item->vat_rate_id, $invoice_items_fresh[0]->vat_rate_id);
        $this->assertSame($invoice_item->quantity, $invoice_items_fresh[0]->quantity);
        $this->assertSame($this->user->id, $invoice_items_fresh[0]->creator_id);

        // CreateInvoice taxes
        $this->assertSame($invoice->id, $invoice_taxes_fresh[0]->invoice_id);
        $this->assertSame($invoice_tax_report->vat_rate_id, $invoice_taxes_fresh[0]->vat_rate_id);
        $this->assertSame($invoice_tax_report->price_net, $invoice_taxes_fresh[0]->price_net);
        $this->assertSame($invoice_tax_report->price_gross, $invoice_taxes_fresh[0]->price_gross);
    }

    /** @test */
    public function update_data_invoice_with_online_sale()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
            'creator_id' => $this->user->id,
        ]);
        $invoice_tax_report = factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $online_sale = factory(OnlineSale::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->onlineSales()->attach($online_sale);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $invoice_payments_count = InvoicePayment::count();
        $invoice_items_count = InvoiceItem::count();
        $invoice_tax_reports_count = InvoiceTaxReport::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09',
            'price_net' => 12.34,
            'price_gross' => 14.85,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame($invoice_items_count, InvoiceItem::count());
        $this->assertSame($invoice_tax_reports_count, InvoiceTaxReport::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->correction_type, $invoice_fresh->correction_type);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame($invoice->sale_date, $invoice_fresh->sale_date);
        $this->assertSame($invoice->paid_at, $invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame($invoice->price_net, $invoice_fresh->price_net);
        $this->assertSame($invoice->price_gross, $invoice_fresh->price_gross);
        $this->assertSame($invoice->vat_sum, $invoice_fresh->vat_sum);
        $this->assertSame($invoice->payment_left, $invoice_fresh->payment_left);
        $this->assertSame($invoice->payment_term_days, $invoice_fresh->payment_term_days);
        $this->assertSame($invoice->payment_method_id, $invoice_fresh->payment_method_id);
        $this->assertSame($invoice->paid_at, $invoice_fresh->paid_at);
        $this->assertSame($invoice->gross_counted, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        $this->assertSame($invoice->id, $invoice_items_fresh[0]->invoice_id);
        $this->assertSame($invoice_item->company_service_id, $invoice_items_fresh[0]->company_service_id);
        $this->assertSame($invoice_item->name, $invoice_items_fresh[0]->name);
        $this->assertSame($invoice_item->custom_name, $invoice_items_fresh[0]->custom_name);
        $this->assertSame($invoice_item->price_net_sum, $invoice_items_fresh[0]->price_net_sum);
        $this->assertSame($invoice_item->price_gross_sum, $invoice_items_fresh[0]->price_gross_sum);
        $this->assertSame($invoice_item->vat_sum, $invoice_items_fresh[0]->vat_sum);
        $this->assertSame($invoice_item->vat_rate_id, $invoice_items_fresh[0]->vat_rate_id);
        $this->assertSame($invoice_item->quantity, $invoice_items_fresh[0]->quantity);
        $this->assertSame($this->user->id, $invoice_items_fresh[0]->creator_id);

        // CreateInvoice taxes
        $this->assertSame($invoice->id, $invoice_taxes_fresh[0]->invoice_id);
        $this->assertSame($invoice_tax_report->vat_rate_id, $invoice_taxes_fresh[0]->vat_rate_id);
        $this->assertSame($invoice_tax_report->price_net, $invoice_taxes_fresh[0]->price_net);
        $this->assertSame($invoice_tax_report->price_gross, $invoice_taxes_fresh[0]->price_gross);
    }

    /** @test */
    public function update_data_invoice_without_receipt_and_online_sale()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $company->defaultBankAccount()->update([
            'bank_name' => 'Testbankname1',
            'number' => '5445249451054',
        ]);

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'order_number_date' => '2017-02-01',
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'order_number_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'description' => 'sample description',
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count, CashFlow::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->correction_type, $invoice_fresh->correction_type);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertSame('2017-02-01', $invoice_fresh->order_number_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1481, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(1481, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame($company->defaultBankAccount()->id, $invoice_fresh->bank_account_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);
        $this->assertSame('sample description', $invoice_fresh->description);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertSame($company->defaultBankAccount()->bank_name, $invoice_company_fresh->bank_name);
        $this->assertSame($company->defaultBankAccount()->number, $invoice_company_fresh->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        $company_service = $company_service->fresh();
        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame(4, $company_service->is_used);
            $this->assertSame($item['custom_name'], $invoice_items_fresh[$key]->custom_name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_data_invoice_without_receipt_and_online_sale_custom_items_name_turn_off_and_without_bank_account()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            false
        );
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $corrected_invoice = factory(Invoice::class)->create([
            'delivery_address_id' => $delivery_address->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'corrected_invoice_id' => $corrected_invoice->id,
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,

        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count, CashFlow::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->correction_type, $invoice_fresh->correction_type);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1481, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(1481, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertNull($invoice_company_fresh->bank_name);
        $this->assertNull($invoice_company_fresh->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame(null, $invoice_items_fresh[$key]->custom_name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_not_sending_custom_name_while_its_turn_on_throw_error()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        foreach ($items_data as $key => $item) {
            unset($item['custom_name']);
            $items_data[$key] = $item;
        }
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.custom_name',
        ]);
    }

    /** @test */
    public function update_validation_error_try_updating_correction()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();

        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $invoice->invoice_type_id = $invoice_type->id;
        $invoice->save();

        $invoice_vat = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);

        $invoice->nodeInvoices()->attach($invoice_vat->id);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'id',
        ]);
    }

    /** @test */
    public function update_data_invoice_with_other_invoice_amount_and_bank_account_empty()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $corrected_invoice = factory(Invoice::class)->create([
            'delivery_address_id' => $delivery_address->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'corrected_invoice_id' => $corrected_invoice->id,
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,

        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 1475,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'bank_account_id' => '',
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count, CashFlow::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(14, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertNull($invoice_company_fresh->bank_name);
        $this->assertNull($invoice_company_fresh->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_data_invoice_with_other_invoice_amount_for_cash_and_bank_account_is_null()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $now = Carbon::parse('2017-02-13 08:09:10');
        Carbon::setTestNow($now);

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $corrected_invoice = factory(Invoice::class)->create([
            'delivery_address_id' => $delivery_address->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'corrected_invoice_id' => $corrected_invoice->id,
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,

        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 1481,
            'payment_method_id' => $payment_method->id,
        ]);
        factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'user_id' => $this->user->id,
            'direction' => CashFlow::DIRECTION_IN,
            'amount' => 1481,
            'cashless' => 0,
            'flow_date' => Carbon::now(),
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'bank_account_id' => null,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count + 2, CashFlow::count());
        $this->assertSame($invoice_payments_count, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertEquals('2017-02-13 08:09:10', $invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(0, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertNull($invoice_company_fresh->bank_name);
        $this->assertNull($invoice_company_fresh->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_data_invoice_with_other_payment_method_id()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $other_payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $corrected_invoice = factory(Invoice::class)->create([
            'delivery_address_id' => $delivery_address->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'corrected_invoice_id' => $corrected_invoice->id,
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1489,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'amount' => 1475,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $other_payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count, CashFlow::count());
        $this->assertSame(0, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(1489, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($other_payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_data_invoice_with_payment_method_cash()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();
        PaymentMethod::whereRaw('1 = 1')->delete();

        $now = Carbon::parse('2017-02-13 08:09:10');
        Carbon::setTestNow($now);

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $other_payment_method = factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::CASH,
        ]);
        factory(PaymentMethod::class)->create([
            'slug' => PaymentMethodType::DEBIT_CARD,
        ]);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $corrected_invoice = factory(Invoice::class)->create([
            'delivery_address_id' => $delivery_address->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'corrected_invoice_id' => $corrected_invoice->id,
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1489,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $invoice_payments_count = InvoicePayment::count();
        $cash_flow_count = CashFlow::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 11,
            'payment_method_id' => $other_payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])
            ->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count + 1, CashFlow::count());
        $this->assertSame($invoice_payments_count + 1, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;
        $invoice_payments_fresh = $invoice_fresh->payments;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertEquals('2017-02-13 08:09:10', $invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(0, $invoice_fresh->payment_left);
        $this->assertSame(11, $invoice_fresh->payment_term_days);
        $this->assertSame($other_payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice payment
        $this->assertSame(1489, $invoice_payments_fresh[0]->amount);
        $this->assertSame($other_payment_method->id, $invoice_payments_fresh[0]->payment_method_id);
        $this->assertSame($this->user->id, $invoice_payments_fresh[0]->registrar_id);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }
    }

    /** @test */
    public function update_data_invoice_with_cash_flow_checking_cash_out()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $now = Carbon::parse('2017-02-13 08:09:10');
        Carbon::setTestNow($now);

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $new_payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1984,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        factory(CashFlow::class)->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'user_id' => $this->user->id,
            'direction' => CashFlow::DIRECTION_IN,
            'amount' => 1234,
            'cashless' => 0,
            'flow_date' => Carbon::now(),
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $payment_method->id,
        ]);

        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();
        $cash_flow_count = CashFlow::count();
        $invoice_payments_count = InvoicePayment::count();

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $new_payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(count($taxes_data), InvoiceTaxReport::count());
        $this->assertSame($cash_flow_count + 1, CashFlow::count());
        $this->assertSame($invoice_payments_count - 1, InvoicePayment::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_contractor_fresh = $invoice_contractor->fresh();
        $invoice_company_fresh = $invoice_company->fresh();
        $invoice_items_fresh = $invoice_fresh->items;
        $invoice_taxes_fresh = $invoice_fresh->taxes;
        $invoice_cash_flows = $invoice_fresh->cashFlows;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(1489, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($new_payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);

        // CreateInvoice contractor
        $this->assertSame($invoice->id, $invoice_contractor_fresh->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor_fresh->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor_fresh->name);
        $this->assertSame($contractor->vatin, $invoice_contractor_fresh->vatin);
        $this->assertSame($contractor->email, $invoice_contractor_fresh->email);
        $this->assertSame($contractor->phone, $invoice_contractor_fresh->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor_fresh->bank_name);
        $this->assertSame($contractor->bank_account_number, $invoice_contractor_fresh->bank_account_number);
        $this->assertSame($contractor->main_address_street, $invoice_contractor_fresh->main_address_street);
        $this->assertSame($contractor->main_address_number, $invoice_contractor_fresh->main_address_number);
        $this->assertSame($contractor->main_address_zip_code, $invoice_contractor_fresh->main_address_zip_code);
        $this->assertSame($contractor->main_address_city, $invoice_contractor_fresh->main_address_city);
        $this->assertSame($contractor->main_address_country, $invoice_contractor_fresh->main_address_country);

        // CreateInvoice company
        $this->assertSame($invoice->id, $invoice_company_fresh->invoice_id);
        $this->assertSame($company->id, $invoice_company_fresh->company_id);
        $this->assertSame($company->name, $invoice_company_fresh->name);
        $this->assertSame($company->vatin, $invoice_company_fresh->vatin);
        $this->assertSame($company->email, $invoice_company_fresh->email);
        $this->assertSame($company->phone, $invoice_company_fresh->phone);
        $this->assertSame($company->main_address_street, $invoice_company_fresh->main_address_street);
        $this->assertSame($company->main_address_number, $invoice_company_fresh->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company_fresh->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company_fresh->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company_fresh->main_address_country);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }

        // CreateInvoice taxes
        foreach ($taxes_data as $key => $tax) {
            $this->assertSame($invoice->id, $invoice_taxes_fresh[$key]->invoice_id);
            $this->assertSame($vat_rate->id, $invoice_taxes_fresh[$key]->vat_rate_id);
            $this->assertSame(normalize_price($tax['price_net']), $invoice_taxes_fresh[$key]->price_net);
            $this->assertSame(normalize_price($tax['price_gross']), $invoice_taxes_fresh[$key]->price_gross);
        }

        // CreateInvoice cash flows
        $this->assertSame($company->id, $invoice_cash_flows[0]->company_id);
        $this->assertSame($invoice->id, $invoice_cash_flows[0]->invoice_id);
        $this->assertSame($this->user->id, $invoice_cash_flows[0]->user_id);
        $this->assertSame(CashFlow::DIRECTION_IN, $invoice_cash_flows[0]->direction);
        $this->assertSame(1234, $invoice_cash_flows[0]->amount);
        $this->assertSame(0, $invoice_cash_flows[0]->cashless);
        $this->assertSame('2017-02-13', $invoice_cash_flows[0]->flow_date);

        $this->assertSame($company->id, $invoice_cash_flows[1]->company_id);
        $this->assertSame($invoice->id, $invoice_cash_flows[1]->invoice_id);
        $this->assertSame($this->user->id, $invoice_cash_flows[1]->user_id);
        $this->assertSame(CashFlow::DIRECTION_OUT, $invoice_cash_flows[1]->direction);
        $this->assertSame(1234, $invoice_cash_flows[1]->amount);
        $this->assertSame(0, $invoice_cash_flows[1]->cashless);
        $this->assertSame('2017-02-13', $invoice_cash_flows[1]->flow_date);
    }

    /** @test */
    public function update_invoice_and_check_adding_special_partial_payment()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $bank_transfer->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $bank_transfer->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'special_payment' => [
                'amount' => 14.50,
                'payment_method_id' => $cash->id,
            ],
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($bank_transfer->id, $invoice->payment_method_id);

        $this->assertCount(1, $invoice->payments);
        $payment = $invoice->payments->first();
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1450, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
    }

    /** @test */
    public function update_invoice_and_check_updating_special_partial_payment()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $bank_transfer->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $sp_payment = factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $card->id,
            'amount' => 100,
            'special_partial_payment' => true,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $bank_transfer->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'special_payment' => [
                'amount' => 14.50,
                'payment_method_id' => $cash->id,
            ],
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($bank_transfer->id, $invoice->payment_method_id);

        $this->assertCount(1, $invoice->payments);
        $payment = $invoice->payments->first();
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1450, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
    }

    /** @test */
    public function update_invoice_and_check_deleting_special_partial_payment()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $bank_transfer->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $sp_payment = factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'payment_method_id' => $card->id,
            'amount' => 100,
            'special_partial_payment' => true,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $bank_transfer->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($bank_transfer->id, $invoice->payment_method_id);

        $this->assertCount(0, $invoice->payments);
    }

    /** @test */
    public function update_invoice_to_partial_payment_and_make_sure_payments_and_cash_flows_are_valid()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $cash->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice->payments()->create(['amount' => 15, 'payment_method_id' => $cash->id]);
        $invoice->cashFlows()->create(['amount' => 1500, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $cash->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'special_payment' => [
                'amount' => 12.00,
                'payment_method_id' => $card->id,
            ],
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($cash->id, $invoice->payment_method_id);
        $this->assertCount(2, $invoice->payments);
        $payment = $invoice->payments[0];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1200, $payment->amount);
        $this->assertEquals($card->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
        $payment = $invoice->payments[1];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(289, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
        $this->assertCount(4, $invoice->cashFlows);
        $cashFlow = $invoice->cashFlows[0];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1500, $cashFlow->amount);
        $this->assertEquals(0, $cashFlow->cashless);
        $this->assertNull($cashFlow->deleted_at);
        $cashFlow = $invoice->cashFlows[1];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_OUT, $cashFlow->direction);
        $this->assertEquals(1500, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
        $cashFlow = $invoice->cashFlows[2];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1200, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(1, $cashFlow->cashless);
        $cashFlow = $invoice->cashFlows[3];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(289, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
    }

    /** @test */
    public function update_invoice_when_total_was_not_changed_but_partial_payment_was_added()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1234,
            'price_gross' => 1489,
            'vat_sum' => 300,
            'payment_left' => 0,
            'payment_term_days' => 4,
            'payment_method_id' => $cash->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice->payments()->create(['amount' => 14.89, 'payment_method_id' => $cash->id]);
        $invoice->cashFlows()->create(['amount' => 1489, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $cash->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'special_payment' => [
                'amount' => 12.00,
                'payment_method_id' => $card->id,
            ],
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($cash->id, $invoice->payment_method_id);
        $this->assertCount(2, $invoice->payments);
        $payment = $invoice->payments[0];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1200, $payment->amount);
        $this->assertEquals($card->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
        $payment = $invoice->payments[1];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(289, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
        $this->assertCount(4, $invoice->cashFlows);
        $cashFlow = $invoice->cashFlows[0];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1489, $cashFlow->amount);
        $this->assertEquals(0, $cashFlow->cashless);
        $this->assertNull($cashFlow->deleted_at);
        $cashFlow = $invoice->cashFlows[1];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_OUT, $cashFlow->direction);
        $this->assertEquals(1489, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
        $cashFlow = $invoice->cashFlows[2];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1200, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(1, $cashFlow->cashless);
        $cashFlow = $invoice->cashFlows[3];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(289, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
    }

    /** @test */
    public function update_invoice_when_total_was_not_changed_but_partial_payment_was_removed()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1234,
            'price_gross' => 1489,
            'vat_sum' => 300,
            'payment_left' => 0,
            'payment_term_days' => 4,
            'payment_method_id' => $cash->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice->payments()->create(['amount' => 12.00, 'payment_method_id' => $cash->id, 'special_partial_payment' => 1]);
        $invoice->payments()->create(['amount' => 2.89, 'payment_method_id' => $card->id, 'special_partial_payment' => 1]);
        $invoice->cashFlows()->create(['amount' => 1200, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id, 'cashless' => 0]);
        $invoice->cashFlows()->create(['amount' => 289, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id, 'cashless' => 1]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $cash->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($cash->id, $invoice->payment_method_id);
        $this->assertCount(1, $invoice->payments);
        $payment = $invoice->payments[0];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1489, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(0, $payment->special_partial_payment);

        $cash_flows = $invoice->cashFlows()->orderBy('id', 'ASC')->get();
        $this->assertCount(4, $cash_flows);
        $cashFlow = $cash_flows[0];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1200, $cashFlow->amount);
        $this->assertEquals(0, $cashFlow->cashless);
        $this->assertNull($cashFlow->deleted_at);
        $cashFlow = $cash_flows[1];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(289, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(1, $cashFlow->cashless);
        $cashFlow = $cash_flows[2];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_OUT, $cashFlow->direction);
        $this->assertEquals(1489, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
        $cashFlow = $cash_flows[3];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1489, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
    }

    /** @test */
    public function update_invoice_when_total_was_not_changed_but_partial_payment_was_updated()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1234,
            'price_gross' => 1489,
            'vat_sum' => 300,
            'payment_left' => 0,
            'payment_term_days' => 4,
            'payment_method_id' => $cash->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice->payments()->create(['amount' => 12.00, 'payment_method_id' => $cash->id, 'special_partial_payment' => 1]);
        $invoice->payments()->create(['amount' => 2.89, 'payment_method_id' => $card->id, 'special_partial_payment' => 1]);
        $invoice->cashFlows()->create(['amount' => 1200, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id, 'cashless' => 0]);
        $invoice->cashFlows()->create(['amount' => 289, 'direction' => CashFlow::DIRECTION_IN, 'user_id' => $this->user->id, 'company_id' => $company->id, 'cashless' => 1]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $cash->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
            'special_payment' => [
                'amount' => 11.00,
                'payment_method_id' => $card->id,
            ],
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();
        $this->assertEquals($cash->id, $invoice->payment_method_id);
        $this->assertCount(2, $invoice->payments);
        $payment = $invoice->payments[0];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(1100, $payment->amount);
        $this->assertEquals($card->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
        $payment = $invoice->payments[1];
        $this->assertEquals($invoice->id, $payment->invoice_id);
        $this->assertEquals(389, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);

        $cash_flows = $invoice->cashFlows()->orderBy('id', 'ASC')->get();
        $this->assertCount(5, $cash_flows);
        $cashFlow = $cash_flows[0];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1200, $cashFlow->amount);
        $this->assertEquals(0, $cashFlow->cashless);
        $this->assertNull($cashFlow->deleted_at);
        $cashFlow = $cash_flows[1];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(289, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(1, $cashFlow->cashless);
        $cashFlow = $cash_flows[2];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_OUT, $cashFlow->direction);
        $this->assertEquals(1489, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
        $cashFlow = $cash_flows[3];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(1100, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(1, $cashFlow->cashless);
        $cashFlow = $cash_flows[4];
        $this->assertEquals($invoice->id, $cashFlow->invoice_id);
        $this->assertEquals(CashFlow::DIRECTION_IN, $cashFlow->direction);
        $this->assertEquals(389, $cashFlow->amount);
        $this->assertNull($cashFlow->deleted_at);
        $this->assertEquals(0, $cashFlow->cashless);
    }

    /** @test */
    public function update_proforma_in_database()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $now = Carbon::parse('2017-02-13 08:09:10');
        Carbon::setTestNow($now);

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'test',
        ]);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1984,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => $invoice_type->id,
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $items_data_expected = $this->items_data_inputs_expected($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);
        $invoice_count = Invoice::count();
        $invoice_company_count = InvoiceCompany::count();
        $invoice_contractor_count = InvoiceContractor::count();

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $this->assertSame($invoice_count, Invoice::count());
        $this->assertSame($invoice_company_count, InvoiceCompany::count());
        $this->assertSame($invoice_contractor_count, InvoiceContractor::count());
        $this->assertSame(count($items_data), InvoiceItem::count());
        $this->assertSame(0, InvoicePayment::count());
        $this->assertSame(0, CashFlow::count());

        // Fresh data
        $invoice_fresh = $invoice->fresh();
        $invoice_items_fresh = $invoice_fresh->items;

        // CreateInvoice
        $this->assertSame($invoice->number, $invoice_fresh->number);
        $this->assertSame($invoice->order_number, $invoice_fresh->order_number);
        $this->assertSame($invoice->invoice_registry_id, $invoice_fresh->invoice_registry_id);
        $this->assertSame($invoice->drawer_id, $invoice_fresh->drawer_id);
        $this->assertSame($invoice->company_id, $invoice_fresh->company_id);
        $this->assertSame($invoice->contractor_id, $invoice_fresh->contractor_id);
        $this->assertSame($invoice->corrected_invoice_id, $invoice_fresh->corrected_invoice_id);
        $this->assertSame('2017-02-09', $invoice_fresh->sale_date);
        $this->assertNull($invoice_fresh->paid_at);
        $this->assertSame('2017-02-02', $invoice_fresh->issue_date);
        $this->assertSame($invoice->invoice_type_id, $invoice_fresh->invoice_type_id);
        $this->assertSame(1234, $invoice_fresh->price_net);
        $this->assertSame(1489, $invoice_fresh->price_gross);
        $this->assertSame(1578, $invoice_fresh->vat_sum);
        $this->assertSame(1489, $invoice_fresh->payment_left);
        $this->assertSame(5, $invoice_fresh->payment_term_days);
        $this->assertSame($payment_method->id, $invoice_fresh->payment_method_id);
        $this->assertSame(1, $invoice_fresh->gross_counted);
        $this->assertNull($invoice_fresh->invoice_margin_procedure_id);

        // CreateInvoice items
        foreach ($items_data_expected as $key => $item) {
            $this->assertSame($invoice->id, $invoice_items_fresh[$key]->invoice_id);
            $this->assertSame($company_service->id, $invoice_items_fresh[$key]->company_service_id);
            $this->assertSame($company_service->name, $invoice_items_fresh[$key]->name);
            $this->assertSame($item['price_net_sum'], $invoice_items_fresh[$key]->price_net_sum);
            $this->assertSame($item['price_gross_sum'], $invoice_items_fresh[$key]->price_gross_sum);
            $this->assertSame($item['vat_sum'], $invoice_items_fresh[$key]->vat_sum);
            $this->assertSame($item['vat_rate_id'], $invoice_items_fresh[$key]->vat_rate_id);
            $this->assertSame($item['quantity'], $invoice_items_fresh[$key]->quantity);
            $this->assertSame($this->user->id, $invoice_items_fresh[$key]->creator_id);
        }
    }

    /** @test */
    public function update_check_adding_service_unit_to_invoice_items()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $bank_transfer->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $invoice_items = $invoice->items;

        foreach ($invoice_items as $item) {
            $this->assertEquals('sztuka', $item->serviceUnit->name);
        }

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $bank_transfer->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $invoice = $invoice->fresh();

        foreach ($invoice->items as $item) {
            $this->assertEquals('kilogram', $item->serviceUnit->name);
        }
    }

    /** @test */
    public function update_check_normalizing_quantity()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1200,
            'price_gross' => 1500,
            'vat_sum' => 300,
            'payment_left' => 1500,
            'payment_term_days' => 4,
            'payment_method_id' => $bank_transfer->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.89,
            'vat_sum' => 15.78,
            'payment_term_days' => 5,
            'payment_method_id' => $bank_transfer->id,
            'bank_account_id' => $company->defaultBankAccount()->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $invoice_items = $invoice->fresh()->items;

        $this->assertEquals(-1000, $invoice_items[0]->quantity);
        $this->assertEquals(-10000, $invoice_items[1]->quantity);
        $this->assertEquals(100000, $invoice_items[2]->quantity);
        $this->assertEquals(1000000, $invoice_items[3]->quantity);
    }

    /** @test */
    public function update_it_sets_valid_contractor_ids_when_updaing_contractor()
    {
        InvoiceReceipt::whereRaw('1 = 1')->delete();
        InvoiceOnlineSale::whereRaw('1 = 1')->delete();
        CashFlow::whereRaw('1 = 1')->delete();

        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->name = 'Testname1';
        $company->vatin = 'Testvatin1';
        $company->email = 'Testemail1';
        $company->phone = '123456789';
        $company->main_address_street = 'Teststreet1';
        $company->main_address_number = 'Testnumber1';
        $company->main_address_zip_code = '84-754';
        $company->main_address_city = 'Testcity1';
        $company->main_address_country = 'Testcountry1';
        $company->save();

        $vat_rate = factory(VatRate::class)->create();
        $company_service = factory(CompanyService::class)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'Testname1',
            'vatin' => 'Testvatin1',
            'email' => 'Testemail1',
            'phone' => '123456789',
            'bank_name' => 'Testbankname1',
            'bank_account_number' => '5445249451054',
            'main_address_street' => 'Teststreet1',
            'main_address_number' => 'Testnumber1',
            'main_address_zip_code' => '84-754',
            'main_address_city' => 'Testcity1',
            'main_address_country' => 'Testcountry1',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'issue_date' => '2017-02-01',
            'sale_date' => '2017-02-05',
            'price_net' => 1232,
            'price_gross' => 1481,
            'vat_sum' => 1572,
            'payment_left' => 6,
            'payment_term_days' => 4,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 0,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'name' => 'Testname2',
            'vatin' => 'Testvatin2',
            'email' => 'Testemail2',
            'phone' => '123456782',
            'bank_name' => 'Testbankname2',
            'bank_account_number' => '5445249451052',
            'main_address_street' => 'Teststreet2',
            'main_address_number' => 'Testnumber2',
            'main_address_zip_code' => '84-752',
            'main_address_city' => 'Testcity2',
            'main_address_country' => 'Testcountry2',
        ]);
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
            'print_on_invoice' => false,
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        $new_contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'name' => 'New Testname1',
            'vatin' => 'New Testvatin1',
            'email' => 'New Testemail1',
            'phone' => 'New 123456789',
            'bank_name' => 'New Testbankname1',
            'bank_account_number' => 'New 5445249451054',
            'main_address_street' => 'New Teststreet1',
            'main_address_number' => 'New Testnumber1',
            'main_address_zip_code' => '88-000',
            'main_address_city' => 'New Testcity1',
            'main_address_country' => 'New Testcountry1',
            'country_vatin_prefix_id' => 'UK',
        ]);

        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'contractor_id' => $new_contractor->id,
            'correction_type' => InvoiceCorrectionType::PRICE,
            'issue_date' => '2017-02-02',
            'sale_date' => '2017-02-09',
            'paid_at' => '2017-02-09 00:00:00',
            'price_net' => 12.34,
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_left' => 7,
            'payment_term_days' => 5,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->seeStatusCode(200);

        $invoice = $invoice->fresh();
        $this->assertSame($new_contractor->id, $invoice->contractor_id);

        $invoice_contractor = $invoice->invoiceContractor;

        $fields_to_compare = [
            'name',
            'country_vatin_prefix_id',
            'vatin',
            'email',
            'phone',
            'bank_name',
            'bank_account_number',
            'main_address_number',
            'main_address_zip_code',
            'main_address_city',
            'main_address_country',
        ];

        $this->assertEquals(
            array_only($new_contractor->toArray(), $fields_to_compare),
            array_only($invoice_contractor->toArray(), $fields_to_compare)
        );
        $this->assertSame($new_contractor->id, $invoice_contractor->contractor_id);
    }

    /** @test */
    public function update_decrement_company_service_used()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $company_service = factory(CompanyService::class)->create([
            'is_used' => 5,
        ]);
        $this->assertSame(1, $invoice->items()->count());
        $invoice->items()->update([
            'company_service_id' => $company_service->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $this->assertSame($company_service->fresh()->is_used, 4);
    }

    /** @test */
    public function update_decrement_to_zero_company_service_used()
    {
        list($company, $payment_method, $contractor, $invoice, $items_data, $taxes_data) = $this->setFinancialEnvironmentForUpdate();
        $company_service = factory(CompanyService::class)->create([
            'is_used' => 0,
        ]);
        $this->assertSame(1, $invoice->items()->count());
        $invoice->items()->update([
            'company_service_id' => $company_service->id,
        ]);
        $this->put('invoices/' . $invoice->id . '?selected_company_id=' . $company->id, [
            'price_gross' => 14.81,
            'vat_sum' => 15.78,
            'payment_method_id' => $payment_method->id,
            'gross_counted' => 1,
            'items' => $items_data,
            'taxes' => $taxes_data,
        ])->assertResponseStatus(200);

        $this->assertSame($company_service->fresh()->is_used, 0);
    }
}
