<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload;

use App\Models\Db\BankAccount;
use App\Models\Db\Contractor as ModelContractor;
use App\Models\Db\ContractorAddress;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceCompany as ModelInvoiceCompany;
use App\Models\Db\InvoiceContractor as ModelInvoiceContractor;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\InvoiceInvoice as ModelInvoiceInvoice;
use App\Models\Db\InvoiceItem as ModelInvoiceItem;
use App\Models\Db\InvoiceOnlineSale as ModelInvoiceOnlineSale;
use App\Models\Db\InvoiceReceipt as ModelInvoiceReceipt;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceTaxReport as ModelInvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use App\Models\Db\Package;
use App\Models\Db\Receipt as ModelReceipt;
use App\Models\Db\ServiceUnit;
use App\Models\Db\User;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\InvoiceReverseChargeType;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class FinancialEnvironment extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /**
     * @return array
     */
    public function setInvoicePrintingEnvironment($package = null): array
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
        $company = $this->login_user_and_return_company_with_his_employee_role($package);
        $company->name = 'aaffcc';
        $company->save();
        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 1,
            'drawer_id' => $drawer->id,
            'description' => 'sample description',
        ]);
        $contractor_invoice = factory(ModelInvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => 789456789,
            'country_vatin_prefix_id' => 1,
        ]);
        $company_invoice = factory(ModelInvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
            'country_vatin_prefix_id' => 2,
            'phone' => 789456123,
        ]);
        $invoice_items = factory(ModelInvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'pkwiu' => '11.11.11.1',
            'quantity' => 10000,
            'print_on_invoice' => false,
            'description' => 'Some description',
            'service_unit_id' => ServiceUnit::findBySlug(ServiceUnit::SERVICE)->id,
        ]);
        $invoice_items[0]->vatRate->name = 'vat_name_a1';
        $invoice_items[0]->vatRate->save();
        $invoice_items[1]->update([
            'name' => 'name_2',
            'price_net' => 1100,
            'price_net_sum' => 3300,
            'price_gross' => 1200,
            'vat_sum' => 300,
            'pkwiu' => '22.22.22.2',
            'price_gross_sum' => 3600,
            'quantity' => 3000,
            'service_unit_id' => ServiceUnit::findBySlug('km')->id,
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(ModelInvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_tax_reports[0]->vatRate->name = 'vat_name';
        $invoice_tax_reports[0]->vatRate->save();
        $invoice_tax_reports[0]->update([
            'price_net' => 80,
            'price_gross' => 180,
        ]);
        $invoice_tax_reports[1]->vatRate->name = 'vat_name_2';
        $invoice_tax_reports[1]->vatRate->save();
        $invoice_tax_reports[1]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);
        $invoice->paymentMethod->id = 1;
        $invoice->paymentMethod->save();

        $receiver = factory(ModelContractor::class)->create([
            'name' => 'receiver_name',
        ]);
        $invoice_delivery_address = factory(InvoiceDeliveryAddress::class)->create([
            'receiver_id' => $receiver->id,
            'receiver_name' => $receiver->name,
            'invoice_id' => $invoice->id,
            'street' => 'grunwaldzka',
            'number' => '2',
            'zip_code' => '62-000',
            'city' => 'Lubon',
            'country' => 'Polska',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create();
        $invoice->delivery_address_id = $delivery_address->id;
        $invoice->save();

        return [$directory, $file, $text_file, $company, $invoice];
    }

    protected function login_user_and_return_company_with_his_employee_role($package = null)
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, $package);

        return $company;
    }

    /**
     * Set invoice for attributes.
     *
     * @return array
     */
    protected function setInvoiceForAttributes(): array
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company->country_vatin_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()
            ->id;
        $company->name = 'Test Company';
        $company->main_address_street = 'Testowa';
        $company->main_address_number = '1/12';
        $company->main_address_zip_code = '11-111';
        $company->main_address_city = 'Test City';
        $company->vatin = '1234567890';

        $company->save();
        ModelInvoice::whereRaw('1 = 1')->delete();
        $company->bankAccounts()->save(factory(BankAccount::class)->make([
            'default' => true,
            'bank_name' => 'Test Bank',
            'number' => '123456789123456789',
        ]));
        $contractor = factory(ModelContractor::class)->create([
            'company_id' => $company->id,
            'country_vatin_prefix_id' => CountryVatinPrefix::where('name', 'Polska')->first()->id,
        ]);
        $contractor_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $invoice = factory(ModelInvoice::class)->create([
            'number' => 123,
            'company_id' => $company->id,
            'contractor_id' => $contractor->id,
            'delivery_address_id' => $contractor_address->id,
            'default_delivery' => 1,
            'drawer_id' => $this->user->id,
            'price_net' => 5425,
            'price_gross' => 6418,
            'vat_sum' => 754,
            'payment_left' => 4854,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::OUT_EU_CUSTOMER_TAX)->id,
            'issue_date' => '2017-08-01',
            'payment_term_days' => 10,
            'description' => 'sample_description',
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
            'quantity' => 12345,
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
            'country_vatin_prefix_id' => $company->country_vatin_prefix_id,
        ]);
        $invoice_contractor = factory(ModelInvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'country_vatin_prefix_id' => $contractor->country_vatin_prefix_id,
        ]);
        $invoice_delivery_address = factory(InvoiceDeliveryAddress::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice->bankAccount()->associate($company->bankAccounts()->first());
        $invoice->save();

        return [$company, $invoice, $invoice_node, $receipt, $invoice_items, $online_sale, $invoice_taxes, $invoice_company, $invoice_contractor, $invoice_delivery_address];
    }

    protected function createInvoice($type = InvoiceTypeStatus::VAT)
    {
        return factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug($type)->id,
            'number' => 'example_number_' . $type,
        ]);
    }

    /**
     * @return array
     */
    protected function setCorrectionInvoicePrintingEnvironment($package = Package::PREMIUM): array
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment($package);

        $invoice_corrected = factory(ModelInvoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'company_id' => $company->id,
            'price_net' => 500,
            'vat_sum' => 1500,
            'price_gross' => 2500,
            'number' => 'sample_number_prototype',
            'payment_method_id' => 2,
        ]);

        $invoice_items_corrected = factory(ModelInvoiceItem::class, 2)->create([
            'invoice_id' => $invoice_corrected->id,
        ]);
        $invoice_items_corrected[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'vat_sum' => 33,
            'price_gross' => 200,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 4,
        ]);
        $invoice_items_corrected[1]->update([
            'name' => 'name_2',
            'price_net' => 1100,
            'price_net_sum' => 11000,
            'vat_sum' => 1000,
            'price_gross' => 1200,
            'price_gross_sum' => 12000,
            'quantity' => 10,
        ]);
        $invoice->update([
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'corrected_invoice_id' => $invoice_corrected->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'price_net' => 4300,
            'price_gross' => 4300,
        ]);
        $invoice->items->first()->update([
            'position_corrected_id' => $invoice_items_corrected[0]->id,
        ]);
        $invoice->items()->skip(1)->first()->update([
            'position_corrected_id' => $invoice_items_corrected[1]->id,
        ]);

        $invoice->taxes()->delete();
        factory(ModelInvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'price_net' => 4300,
            'price_gross' => 4300,
        ]);

        return [$directory, $file, $text_file, $company, $invoice];
    }
}
