<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Helpers;

use App\Models\Db\BankAccount;
use App\Models\Db\CashFlow;
use App\Models\Db\CompanyService;
use App\Models\Db\Contractor;
use App\Models\Db\ContractorAddress;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceOnlineSale;
use App\Models\Db\InvoiceReceipt;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceType;
use App\Models\Db\PaymentMethod;
use App\Models\Db\ServiceUnit;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use App\Models\Other\RoleType;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

trait FinancialEnvironment
{
    protected function invoiceExpectData()
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'price_net' => 1010,
            'vat_sum' => 460,
            'price_gross' => 1111,
            'items' => [
                [
                    'price_net' => 101,
                    'price_net_sum' => 101,
                    'vat_sum' => 10,
                    'price_gross_sum' => 111,
                    'quantity' => 1000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => 'custom_name',
                ],
                [
                    'price_net' => 202,
                    'price_net_sum' => 2020,
                    'vat_sum' => 20,
                    'price_gross_sum' => 2240,
                    'quantity' => 10000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => 'custom_name_2',
                ],
                [
                    'price_net' => 3030,
                    'price_net_sum' => 30300,
                    'vat_sum' => 3033,
                    'price_gross_sum' => 3333300,
                    'quantity' => 100000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => null,
                ],
                [
                    'price_net' => 4040,
                    'price_net_sum' => 4040000,
                    'vat_sum' => 4044,
                    'price_gross_sum' => 4044044,
                    'quantity' => 1000000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => null,
                ],

            ],
        ];
    }

    protected function init_incoming_data(
        Contractor $contractor,
        PaymentMethod $payment_method,
        InvoiceType $invoice_type,
        Collection $company_services,
        VatRate $vat_rate
    ) {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'sale_date' => Carbon::parse('2017-01-15')->toDateString(),
            'issue_date' => Carbon::parse('2017-01-15')->toDateString(),
            'gross_counted' => '0',
            'price_net' => 10.1,
            'price_gross' => 11.11,
            'vat_sum' => 4.6,
            'payment_term_days' => 10,
            'payment_method_id' => $payment_method->id,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => $invoice_type->id,
            'corrected_invoice_id' => 10,
            'invoice_registry_id' => $this->registry->id,
            'description' => 'invoice_description',
            'items' => [
                [
                    'company_service_id' => $company_services[0]->id,
                    'price_net' => 1.01,
                    'price_net_sum' => 1.01,
                    'price_gross_sum' => 1.11,
                    'vat_sum' => 0.1,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 1,
                    'service_unit_id' => $unit_id,
                    'base_document_id' => 1,
                    'corrected_position_id' => 10,
                    'custom_name' => 'custom_name',
                ],
                [
                    'company_service_id' => $company_services[1]->id,
                    'price_net' => 2.02,
                    'price_net_sum' => 20.2,
                    'price_gross_sum' => 22.4,
                    'vat_sum' => 0.2,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 10,
                    'service_unit_id' => $unit_id,
                    'custom_name' => 'custom_name_2',
                    'base_document_id' => 1,
                ],
                [
                    'company_service_id' => $company_services[2]->id,
                    'price_net' => 30.3,
                    'price_net_sum' => 303,
                    'price_gross_sum' => 33333,
                    'vat_sum' => 30.33,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 100,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                    'base_document_id' => 2,
                ],
                [
                    'company_service_id' => $company_services[3]->id,
                    'price_net' => 40.4,
                    'price_net_sum' => 40400,
                    'price_gross_sum' => 40440.44,
                    'vat_sum' => 40.44,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 1000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                    'base_document_id' => 3,
                ],
            ],
            'taxes' => [
                [
                    'vat_rate_id' => $vat_rate->id,
                    'price_net' => 40.4,
                    'price_gross' => 40400,
                ],
            ],
        ];
    }

    protected function invoiceExpectData_gross_count()
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'price_net' => 1010,
            'vat_sum' => 101,
            'price_gross' => 1111,
            'items' => [
                [
                    'price_gross' => 101,
                    'price_net_sum' => 101,
                    'vat_sum' => 10,
                    'price_gross_sum' => 111,
                    'quantity' => 10000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
                [
                    'price_gross' => 202,
                    'price_net_sum' => 2020,
                    'vat_sum' => 20,
                    'price_gross_sum' => 2240,
                    'quantity' => 1000,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
            ],
            'taxes' => [
                [
                    'price_net' => 4040,
                    'price_gross' => 4040000,
                ],
                [
                    'price_net' => 3030,
                    'price_gross' => 3030000,
                ],
            ],
            'advance_taxes' => [
                [
                    'price_net' => 1000,
                    'price_gross' => 2000,
                ],
            ],
        ];
    }

    protected function init_incoming_data_gross_counted(
        Contractor $contractor,
        PaymentMethod $payment_method,
        InvoiceType $invoice_type,
        Collection $company_services,
        VatRate $vat_rate
    ) {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'sale_date' => Carbon::parse('2017-01-15')->toDateString(),
            'issue_date' => Carbon::parse('2017-01-15')->toDateString(),
            'gross_counted' => '1',
            'price_net' => 10.1,
            'price_gross' => 11.11,
            'vat_sum' => 1.01,
            'payment_term_days' => 10,
            'payment_method_id' => $payment_method->id,
            'bank_account_id' => factory(BankAccount::class)->create()->id,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => $invoice_type->id,
            'invoice_registry_id' => $this->registry->id,
            'items' => [
                [
                    'company_service_id' => $company_services[0]->id,
                    'price_gross' => 1.01,
                    'price_net_sum' => 1.01,
                    'price_gross_sum' => 1.11,
                    'vat_sum' => 0.1,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 1,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
                [
                    'company_service_id' => $company_services[1]->id,
                    'price_gross' => 2.02,
                    'price_net_sum' => 20.2,
                    'price_gross_sum' => 22.4,
                    'vat_sum' => 0.2,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 10,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
            ],
            'taxes' => [
                [
                    'vat_rate_id' => $vat_rate->id,
                    'price_net' => 40.4,
                    'price_gross' => 40400,
                ],
                [
                    'vat_rate_id' => $vat_rate->id,
                    'price_net' => 30.3,
                    'price_gross' => 30300,
                ],
            ],
        ];
    }

    protected function invoiceExpectDataCorrectionInvoice(Collection $invoice_items)
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'price_net' => -1010,
            'vat_sum' => -101,
            'price_gross' => -1111,
            'items' => [
                [
                    'price_net' => 101,
                    'price_net_sum' => -101,
                    'vat_sum' => -10,
                    'price_gross_sum' => -111,
                    'quantity' => -1000,
                    'service_unit_id' => $unit_id,
                    'position_corrected_id' => $invoice_items[0]->id,
                    'custom_name' => 'custom_name',
                ],
                [
                    'price_net' => 202,
                    'price_net_sum' => -2020,
                    'vat_sum' => -20,
                    'price_gross_sum' => -2240,
                    'quantity' => -10000,
                    'service_unit_id' => $unit_id,
                    'position_corrected_id' => $invoice_items[1]->id,
                    'custom_name' => null,
                ],
                [
                    'price_net' => 3030,
                    'price_net_sum' => 30300,
                    'vat_sum' => 3033,
                    'price_gross_sum' => 3333300,
                    'quantity' => 100000,
                    'service_unit_id' => $unit_id,
                    'position_corrected_id' => $invoice_items[2]->id,
                    'custom_name' => null,
                ],
                [
                    'price_net' => 4040,
                    'price_net_sum' => 4040000,
                    'vat_sum' => 4044,
                    'price_gross_sum' => 4044044,
                    'quantity' => 1000220,
                    'service_unit_id' => $unit_id,
                    'position_corrected_id' => $invoice_items[3]->id,
                    'custom_name' => null,
                ],

            ],
        ];
    }

    protected function init_incoming_data_correction_invoice(
        Contractor $contractor,
        PaymentMethod $payment_method,
        InvoiceType $invoice_type,
        Collection $company_services,
        VatRate $vat_rate,
        Invoice $invoice,
        Collection $invoice_items
    ) {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            'issue_date' => Carbon::parse('2017-01-15')->toDateString(),
            'sale_date' => Carbon::parse('2017-01-15')->toDateString(),
            'gross_counted' => '0',
            'price_net' => -10.1,
            'price_gross' => -11.11,
            'vat_sum' => -1.01,
            'payment_term_days' => -10,
            'payment_method_id' => $payment_method->id,
            'contractor_id' => $contractor->id,
            'invoice_type_id' => $invoice_type->id,
            'corrected_invoice_id' => $invoice->id,
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'invoice_registry_id' => $this->registry->id,
            'items' => [
                [
                    'position_corrected_id' => $invoice_items[0]->id,
                    'company_service_id' => $company_services[0]->id,
                    'price_net' => 1.01,
                    'price_net_sum' => -1.01,
                    'price_gross_sum' => -1.11,
                    'vat_sum' => -0.1,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => -1,
                    'service_unit_id' => $unit_id,
                    'custom_name' => 'custom_name',
                ],
                [
                    'position_corrected_id' => $invoice_items[1]->id,
                    'company_service_id' => $company_services[1]->id,
                    'price_net' => 2.02,
                    'price_net_sum' => -20.2,
                    'price_gross_sum' => -22.4,
                    'vat_sum' => -0.2,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => -10,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
                [
                    'position_corrected_id' => $invoice_items[2]->id,
                    'company_service_id' => $company_services[2]->id,
                    'price_net' => 30.3,
                    'price_net_sum' => 303,
                    'price_gross_sum' => 33333,
                    'vat_sum' => 30.33,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 100,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
                [
                    'position_corrected_id' => $invoice_items[3]->id,
                    'company_service_id' => $company_services[3]->id,
                    'price_net' => 40.4,
                    'price_net_sum' => 40400,
                    'price_gross_sum' => 40440.44,
                    'vat_sum' => 40.44,
                    'vat_rate_id' => $vat_rate->id,
                    'quantity' => 1000.22,
                    'service_unit_id' => $unit_id,
                    'custom_name' => '',
                ],
            ],
            'taxes' => [
                [
                    'vat_rate_id' => $vat_rate->id,
                    'price_net' => -40.4,
                    'price_gross' => -40400,
                ],
            ],
        ];
    }

    protected function login_user_and_return_company_with_his_employee_role($package = null)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, $package);

        $company->bankAccounts()->save(factory(BankAccount::class)->make(['default' => true]));

        return $company;
    }

    protected function createInvoiceRegistryForCompany($company)
    {
        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%m}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->save();

        return $invoice_registry;
    }

    /**
     * @return array
     */
    protected function setFinancialEnvironment(): array
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->update([
            'company_id' => $company->id,
        ]);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data['delivery_address_id'] = $delivery_address->id;
        $incoming_data['default_delivery'] = 0;
        $incoming_data['bank_account_id'] = $company->defaultBankAccount()->id;

        return [$company, $delivery_address, $incoming_data];
    }

    protected function items_data_inputs($invoice_item, $company_service, $vat_rate)
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 1.01,
                'price_net_sum' => -1.01,
                'price_gross' => 39.19,
                'price_gross_sum' => -1.11,
                'vat_sum' => -0.1,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => -1,
                'service_unit_id' => $unit_id,
                'custom_name' => 'custom_name',
                'print_on_invoice' => true,
                'description' => 'New description',
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 2.02,
                'price_net_sum' => -20.2,
                'price_gross' => 38.28,
                'price_gross_sum' => -22.4,
                'vat_sum' => -0.2,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => -10,
                'service_unit_id' => $unit_id,
                'custom_name' => '',
                'print_on_invoice' => false,
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 30.3,
                'price_net_sum' => 303,
                'price_gross' => 35.32,
                'price_gross_sum' => 33333,
                'vat_sum' => 30.33,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => 100,
                'service_unit_id' => $unit_id,
                'custom_name' => '',
                'print_on_invoice' => false,
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 40.4,
                'price_net_sum' => 40400,
                'price_gross' => 40.41,
                'price_gross_sum' => 40440.44,
                'vat_sum' => 40.44,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => 1000,
                'service_unit_id' => $unit_id,
                'custom_name' => '',
                'print_on_invoice' => false,
            ],
        ];
    }

    protected function taxes_data_input($vat_rate)
    {
        return [
            [
                'price_net' => 10000,
                'price_gross' => 100000,
                'vat_rate_id' => $vat_rate->id,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function setFinancialEnvironmentForUpdate(): array
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
            'print_on_invoice' => true,
            'description' => 'Some secription',
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make(['default' => true]));
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
            'print_on_invoice' => true,
            'description' => 'Old description',
        ]);

        $items_data = $this->items_data_inputs($invoice_item, $company_service, $vat_rate);
        $taxes_data = $this->taxes_data_input($vat_rate);

        return [$company, $payment_method, $contractor, $invoice, $items_data, $taxes_data];
    }

    protected function items_data_inputs_expected($invoice_item, $company_service, $vat_rate)
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::KILOGRAM)->first()->id;

        return [
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 101,
                'price_net_sum' => -101,
                'price_gross' => 3919,
                'price_gross_sum' => -111,
                'vat_sum' => -10,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => -1000,
                'service_unit_id' => $unit_id,
                'custom_name' => 'custom_name',
                'print_on_invoice' => false,
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 202,
                'price_net_sum' => -2020,
                'price_gross' => 3828,
                'price_gross_sum' => -2240,
                'vat_sum' => -20,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => -10000,
                'service_unit_id' => $unit_id,
                'custom_name' => null,
                'print_on_invoice' => false,
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 3030,
                'price_net_sum' => 30300,
                'price_gross' => 3532,
                'price_gross_sum' => 3333300,
                'vat_sum' => 3033,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => 100000,
                'service_unit_id' => $unit_id,
                'custom_name' => null,
                'print_on_invoice' => false,
            ],
            [
                'position_corrected_id' => $invoice_item->id,
                'company_service_id' => $company_service->id,
                'price_net' => 4040,
                'price_net_sum' => 4040000,
                'price_gross' => 4041,
                'price_gross_sum' => 4044044,
                'vat_sum' => 4044,
                'vat_rate_id' => $vat_rate->id,
                'quantity' => 1000000,
                'service_unit_id' => $unit_id,
                'custom_name' => null,
                'print_on_invoice' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function setFinancialEnvironmentForCorrection(): array
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        array_set($incoming_data, 'bank_account_id', $company->defaultBankAccount()->id);

        return [$company, $invoice, $invoice_items, $incoming_data];
    }

    protected function assertNoVatProperties($invoice_type)
    {
        $invoice = Invoice::whereHas('invoiceType', function ($query) use ($invoice_type) {
            $query->where('id', $invoice_type->id);
        })->latest('id')->first();

        $this->assertSame($invoice_type->id, $invoice->invoiceType->id);

        $this->assertEquals($this->incoming_data['price_gross'], denormalize_price($invoice->price_gross));
        $this->assertEquals($this->incoming_data['price_gross'], denormalize_price($invoice->price_net));
        $this->assertEquals(NoVat::vatAmount(), $invoice->vat_sum);
        collect($invoice->items)->reduce(function ($next, $item) {
            $this->assertSame(NoVat::vatAmount(), $item->vat_sum);
            $this->assertEquals($this->np_vat_rate->id, $item->vatRate->id);
            $this->assertEquals($this->incoming_data['items'][$next]['price_gross_sum'], denormalize_price($item->price_net_sum));
            $this->assertEquals($this->incoming_data['items'][$next++]['price_gross_sum'], denormalize_price($item->price_gross_sum));
            $this->assertEquals('10000', $item->price_gross);
            $this->assertNull($item->price_net);

            return $next;
        }, 0);
        if ($invoice_type->slug != InvoiceTypeStatus::FINAL_ADVANCE) {
            collect($invoice->taxes)->reduce(function ($next, $item) {
                $this->assertEquals($this->np_vat_rate->id, $item->vatRate->id);
                $this->assertEquals($this->incoming_data['taxes'][$next]['price_gross'], denormalize_price($item->price_net));
                $this->assertEquals($this->incoming_data['taxes'][$next++]['price_gross'], denormalize_price($item->price_gross));

                return $next;
            }, 0);
        }
    }

    protected function customizeAmountSettingForNoVatPayer()
    {
        $this->company->update([
            'vat_payer' => false,
        ]);

        $valid_price_net = $this->incoming_data['price_gross'];
        array_set($this->incoming_data, 'price_net', $valid_price_net);
        array_set($this->incoming_data, 'vat_sum', NoVat::vatAmount());
        array_set($this->incoming_data, 'gross_counted', NoVat::COUNT_TYPE);

        $this->np_vat_rate = VatRate::findByName(NoVat::VAT_RATE);

        foreach ($this->incoming_data['items'] as $key => $item) {
            array_set($this->incoming_data, 'items.' . $key . '.price_gross', 100);
            $valid_price_net_item =
                array_get($this->incoming_data, 'items.' . $key . '.price_gross');
            array_set($this->incoming_data, 'items.' . $key . '.price_net', $valid_price_net_item);
            $valid_item_price_net_sum =
                array_get($this->incoming_data, 'items.' . $key . '.price_gross_sum');
            array_set(
                $this->incoming_data,
                'items.' . $key . '.price_net_sum',
                $valid_item_price_net_sum
            );
            array_set($this->incoming_data, 'items.' . $key . '.vat_sum', NoVat::vatAmount());
            array_set(
                $this->incoming_data,
                'items.' . $key . '.vat_rate_id',
                $this->np_vat_rate->id
            );
        }

        foreach ($this->incoming_data['taxes'] as $key => $item) {
            $valid_taxes_price_net =
                array_get($this->incoming_data, 'taxes.' . $key . '.price_gross');
            array_set($this->incoming_data, 'taxes.' . $key . '.price_net', $valid_taxes_price_net);
            array_set(
                $this->incoming_data,
                'taxes.' . $key . '.vat_rate_id',
                $this->np_vat_rate->id
            );
        }
    }
}
