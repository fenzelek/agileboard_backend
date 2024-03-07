<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Helpers\ErrorCode;
use App\Models\Db\Contractor;
use App\Models\Db\ContractorAddress;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Db\InvoicePayment;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\PaymentMethod;
use App\Models\Db\Receipt;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\ServiceUnit;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceMarginProcedureType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\Company;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use File;
use Carbon\Carbon;

class PdfTest extends FinancialEnvironment
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_company_id_error()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $company_other = factory(Company::class)->create();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company_other->id,
        ]);
        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseStatus(404);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_with_footer()
    {
        list($directory, $file, $text_file, $company, $invoice) =
            $this->setInvoicePrintingEnvironment(Package::START);

        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Tel.:',
            '789456123',
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'Odbiorca',
            'contractor_name',
            'receiver_name',
            'sielska',
            '10',
            'grunwaldzka',
            '2',
            '60-666',
            'poznan',
            '62-000',
            'Lubon',
            'NIP:',
            'AF789456789',
            'Opis:',
            $invoice->description,
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'gotówka',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Zapłacono',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
            'Strona (1/1)',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_check_separators_in_large_numbers()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $invoice->price_gross = 123456700;
        $invoice->save();
        $array = [
            number_format_output(1300),
            '1 234 567,00',
            'w tym',
            'Zapłacono',
            '1 234 567,00',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_without_footer()
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
        $company->name = 'aaffcc';
        $company->save();

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 1,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Albania',
            'vatin' => 789456789,
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Afganistan',
            'vatin' => 123456789,
            'country_vatin_prefix_id' => CountryVatinPrefix::where('key', CountryVatinPrefix::KEY_POLAND)->first()->id,
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'pkwiu' => '11.11.11.1',
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Some description',
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
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
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

        $receiver = factory(Contractor::class)->create([
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
            'country' => 'Algieria',
        ]);
        $delivery_address = factory(ContractorAddress::class)->create();
        $invoice->delivery_address_id = $delivery_address->id;
        $invoice->save();

        // Disabled footer on the invoice.
        $module = Module::where('slug', ModuleType::INVOICES_FOOTER_ENABLED)->first();
        $company->companyModules()->where('module_id', $module->id)->update(['value' => 0]);

        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'Afganistan',
            'Data wystawienia:',
            $invoice->issue_date,
            'NIP:',
            CountryVatinPrefix::KEY_POLAND . '123456789',
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'Odbiorca',
            'contractor_name',
            'receiver_name',
            'sielska',
            '10',
            'grunwaldzka',
            '2',
            '60-666',
            'poznan',
            '62-000',
            'Lubon',
            'Albania',
            'Algieria',
            'NIP:',
            '789456789',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'gotówka',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Zapłacono',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_without_vatin()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);
        $company->name = 'aaffcc';
        $company->save();

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 1,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => '',
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'pkwiu' => '11.11.11.1',
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Some description',
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
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
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
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',

            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'gotówka',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Zapłacono',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_without_bank_info_remittance()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);
        $company->name = 'aaffcc';
        $company->save();

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 2,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => '',
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
            'bank_name' => 'Pewien bank',
            'bank_account_number' => '12 1234 1234 1234 1234 1234 1324',
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'pkwiu' => '11.11.11.1',
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Some description',
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
            'quantity' => 30000,
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
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
        $invoice->paymentMethod->id = 2;
        $invoice->paymentMethod->save();
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'przelew',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'Bank:',
            'Pewien bank',
            'Nr rachunku:',
            $invoice->invoiceCompany->bank_account_number,
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Razem do zapłaty',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_without_bank_info_prepayment()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);
        $company->name = 'aaffcc';
        $company->save();

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 4,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => '',
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'vatin' => 123456789,
            'bank_name' => 'Pewien bank',
            'main_address_country' => 'Polska',
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'pkwiu' => '11.11.11.1',
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Some description',
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
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
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
        $invoice->paymentMethod->id = 4;
        $invoice->paymentMethod->save();
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'przedpłata',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'Bank:',
            'Pewien bank',
            'Nr rachunku:',
            $invoice->invoiceCompany->bank_account_number,
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Razem do zapłaty',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_with_bank_info_for_special_payment()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        $invoice->invoiceCompany->update([
            'bank_name' => 'Pewien bank',
        ]);

        $invoice->update([
            'payment_method_id' => PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER)->id,
        ]);

        InvoicePayment::whereRaw('1=1')->delete();
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
        ]);

        $array = [
            'Bank:',
            'Pewien bank',
            'Nr rachunku:',
            $invoice->invoiceCompany->bank_account_number,
            'Faktura VAT',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correct_data_without_bank_info_other()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);
        $company->name = 'aaffcc';
        $company->save();

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => PaymentMethod::findBySlug(PaymentMethodType::OTHER)->id,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => '',
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
            'bank_name' => 'Pewien bank',
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'pkwiu' => '11.11.11.1',
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Some description',
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
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
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
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'inne',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'Bank:',
            'Pewien bank',
            'Nr rachunku:',
            $invoice->invoiceCompany->bank_account_number,
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Razem do zapłaty',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_store_last_printed_at_column()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $this->assertNull($invoice->last_printed_at);

        $now = Carbon::parse('2017-01-01 08:09:10');
        $invoice = $invoice->fresh();
        Carbon::setTestNow($now);

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id);

        $invoice = $invoice->fresh();
        $this->assertSame($now->toDateTimeString(), $invoice->last_printed_at);
    }

    /** @test */
    public function pdf_validate_error_duplicate_not_boolean()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id
            . '&duplicate=' . 'not_valid_boolean')
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'duplicate',
        ]);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_duplicate()
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

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 'sample_number',
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'price_gross_sum' => 2000,
        ]);
        $array = [
            'Faktura VAT',
            'Duplikat',
            $invoice->issue_date,
            'Data wydruku duplikatu:',
            Carbon::now()->format('Y-m-d'),
        ];

        ob_start();

        $this->get(
            'invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id
            . '&duplicate=1'
        )
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_duplicate_for_proforma_invoices_is_not_allowed()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'number' => 'sample_number',
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);

        $this->get(
            'invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id
            . '&duplicate=1'
        )->assertResponseStatus(424);

        $this->verifyErrorResponse(424, ErrorCode::INVOICE_DUPLICATE_FOR_PROFORMA_IS_NOT_ALLOWED);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correction_export_data()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice_corrected = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 500,
            'vat_sum' => 1500,
            'price_gross' => 2500,
            'number' => 'sample_number',
            'payment_method_id' => 2,
            'drawer_id' => $drawer->id,
        ]);

        $invoice_items_corrected = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice_corrected->id,
        ]);
        $invoice_items_corrected[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'vat_sum' => 33,
            'pkwiu' => '11.11.11.1',
            'price_gross' => 200,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 4000,
            'service_unit_id' => ServiceUnit::findBySlug(ServiceUnit::RUNNING_METRE)->id,
        ]);
        $invoice_items_corrected[0]->vatRate->name = 'vat_name_k1';
        $invoice_items_corrected[0]->vatRate->save();
        $invoice_items_corrected[1]->update([
            'name' => 'name_2',
            'price_net' => 1100,
            'price_net_sum' => 11000,
            'vat_sum' => 1000,
            'pkwiu' => '22.22.22.2',
            'price_gross' => 1200,
            'price_gross_sum' => 12000,
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Jestem wcześniejszym opisem powyższego towaru/usługi.',
            'service_unit_id' => ServiceUnit::findBySlug(ServiceUnit::RUNNING_METRE)->id,
        ]);
        $invoice_items_corrected[1]->vatRate->name = 'vat_name_k2';
        $invoice_items_corrected[1]->vatRate->save();
        $invoice_tax_reports_corrected = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoice_corrected->id,
        ]);

        $invoice_tax_reports_corrected[0]->vatRate->name = 'vat_name';
        $invoice_tax_reports_corrected[0]->vatRate->save();
        $invoice_tax_reports_corrected[0]->update([
            'price_net' => 80,
            'price_gross' => 180,
        ]);
        $invoice_tax_reports_corrected[1]->vatRate->name = 'vat_name_2';
        $invoice_tax_reports_corrected[1]->vatRate->save();
        $invoice_tax_reports_corrected[0]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);

        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'corrected_invoice_id' => $invoice_corrected->id,
            'company_id' => $company->id,
            'price_net' => -500,
            'vat_sum' => -1500,
            'price_gross' => -2500,
            'number' => 'corrected_number',
            'payment_method_id' => 2,
            'drawer_id' => $drawer->id,
        ]);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => '4564564564',
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => '1231231231',
            'bank_name' => 'Pewien bank',
            'bank_account_number' => '114554651875516462929281',
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_2',
            'price_net' => -100,
            'price_net_sum' => -500,
            'vat_sum' => -500,
            'price_gross' => -200,
            'price_gross_sum' => -1000,
            'position_corrected_id' => $invoice_items_corrected[1]->id,
            'print_name' => 'print_name',
            'quantity' => 5000,
            'print_on_invoice' => true,
            'description' => 'Jestem poprawionym opisem powyższego towaru/usługi.',
            'service_unit_id' => ServiceUnit::findBySlug(ServiceUnit::HOUR)->id,
        ]);
        $invoice_items[0]->vatRate->name = 'vat_name_a1';
        $invoice_items[0]->vatRate->save();
        $invoice_items[1]->update([
            'name' => 'new_item',
            'price_net' => 2000,
            'price_net_sum' => 4000,
            'vat_sum' => 1000,
            'price_gross' => 2500,
            'price_gross_sum' => 5000,
            'print_name' => 'print_name2',
            'quantity' => 2000,
            'service_unit_id' => ServiceUnit::findBySlug(ServiceUnit::TON)->id,
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_tax_reports[0]->vatRate->name = 'vat_name3';
        $invoice_tax_reports[0]->vatRate->save();
        $invoice_tax_reports[0]->update([
            'price_net' => -80,
            'price_gross' => -180,
        ]);
        $invoice_tax_reports[1]->vatRate->name = 'vat_name_4';
        $invoice_tax_reports[1]->vatRate->save();
        $invoice_tax_reports[1]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);

        $receiver = factory(Contractor::class)->create([
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
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'Korekta',
            'company_name',
            'nr',
            'corrected_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '1231231231',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data korekty:',
            $invoice->sale_date,
            'Do dokumentu nr:',
            'sample_number',
            'Wystawionego w dniu:',
            $invoice_corrected->issue_date,
            'Z datą sprzedaży:',
            $invoice_corrected->sale_date,
            'Nabywca',
            'Odbiorca',
            $contractor_invoice->name,
            'receiver_name',
            $contractor_invoice->main_address_street,
            $contractor_invoice->main_address_number,
            'grunwaldzka',
            '2',
            $contractor_invoice->main_address_zip_code,
            $contractor_invoice->main_address_city,
            '62-000',
            'Lubon',
            'NIP:',
            4564564564,
            'Przyczyna korekty:',
            InvoiceCorrectionType::all($company)[InvoiceCorrectionType::QUANTITY],
            'Przed korektą',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'J.m.',
            'VAT',
            'netto',
            'netto',
            'brutto',
            1,
            'name_2',
            10,
            ServiceUnit::findBySlug(ServiceUnit::RUNNING_METRE)->slug,
            number_format_output(1100),
            number_format_output(11000),
            'vat_name_k2',
            number_format_output(12000),
            'Opis:',
            'Jestem wcześniejszym opisem powyższego towaru/usługi.',
            'Po korekcie',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'J.m.',
            'VAT',
            'netto',
            'netto',
            'brutto',
            1,
            'name_2',
            5,
            ServiceUnit::findBySlug(ServiceUnit::HOUR)->slug,
            number_format_output(-100),
            number_format_output(-500),
            'vat_name_a1',
            number_format_output(-1000),
            'Opis:',
            'Jestem poprawionym opisem powyższego towaru/usługi.',
            2,
            'new_item',
            2,
            ServiceUnit::findBySlug(ServiceUnit::TON)->slug,
            number_format_output(2000),
            number_format_output(4000),
            'vat_name_b1',
            number_format_output(5000),
            'Sposób płatności:',
            'przelew',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)
                ->addDays($invoice->payment_term_days)->toDateString(),
            'Razem',
            number_format_output(-500),
            'X',
            number_format_output(-1500),
            number_format_output(-2500),
            'Bank:',
            'Pewien bank',
            'Nr rachunku:',
            '114554651875516462929281',
            'w tym',
            number_format_output(-80),
            'vat_name3',
            number_format_output(-100),
            number_format_output(-180),
            number_format_output(280),
            'vat_name_4',
            number_format_output(1900),
            number_format_output(2180),
            'Razem do zwrotu',
            number_format_output(2500),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT Korekta nr corrected_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_with_receipt_annotation()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 1,
            'drawer_id' => $drawer->id,
        ]);
        $receipt = factory(Receipt::class)->create([
            'transaction_number' => 'ABC456789',
            'number' => 'ABC-001',
            'payment_method_id' => 1,
            'price_gross' => 2000,
        ]);

        $invoice->receipts()->attach($receipt->id);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => 789456789,
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
        ]);
        $invoice_items = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
        ]);
        $invoice_items->vatRate->name = 'vat_name_a1';
        $invoice_items->vatRate->save();
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Do paragonu:',
            'ABC-001',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'NIP:',
            '789456789',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            'Forma płatności',
            'Termin',
            'Kwota',
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'gotówka',
            $receipt->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'Zapłacono',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
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

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_with_receipts()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 4300,
            'vat_sum' => 1300,
            'price_gross' => 5600,
            'number' => 'sample_number',
            'payment_term_days' => -10,
            'payment_method_id' => 1,
            'drawer_id' => $drawer->id,
        ]);
        $receipts = factory(Receipt::class, 3)->create([
            'payment_method_id' => 1,
            'price_gross' => 2000,
        ]);

        $invoice->receipts()->attach($receipts);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'vatin' => 789456789,
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'vatin' => 123456789,
        ]);
        $invoice_items = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'price_gross' => 200,
            'vat_sum' => 1000,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 10000,
        ]);
        $invoice_items->vatRate->name = 'vat_name_a1';
        $invoice_items->vatRate->save();
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'Do paragonów:',
            $receipts->pluck('number')->implode(', '),
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->issue_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'NIP:',
            '789456789',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            'Forma płatności',
            'Termin',
            'Kwota',
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'gotówka',
            $receipts[0]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'gotówka',
            $receipts[1]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'gotówka',
            $receipts[2]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'Zapłacono',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_correction_for_receipts()
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
        $company = $this->login_user_and_return_company_with_his_employee_role(Package::START);

        $drawer = factory(User::class)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invoice_corrected = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'price_net' => 500,
            'vat_sum' => 1500,
            'price_gross' => 2500,
            'number' => 'sample_number',
            'drawer_id' => $drawer->id,
        ]);
        $receipts = factory(Receipt::class, 3)->create([
            'payment_method_id' => 1,
            'price_gross' => 2000,
        ]);

        $invoice_items_corrected = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice_corrected->id,
        ]);
        $invoice_items_corrected[0]->update([
            'name' => 'name_1',
            'price_net' => 100,
            'price_net_sum' => 1000,
            'vat_sum' => 33,
            'pkwiu' => '11.11.11.1',
            'price_gross' => 200,
            'price_gross_sum' => 2000,
            'custom_name' => 'custom_name',
            'quantity' => 4000,
        ]);
        $invoice_items_corrected[0]->vatRate->name = 'vat_name_k1';
        $invoice_items_corrected[0]->vatRate->save();
        $invoice_items_corrected[1]->update([
            'name' => 'name_2',
            'price_net' => 1100,
            'price_net_sum' => 11000,
            'vat_sum' => 1000,
            'pkwiu' => '22.22.22.2',
            'price_gross' => 1200,
            'price_gross_sum' => 12000,
            'quantity' => 10000,
            'print_on_invoice' => true,
            'description' => 'Jestem wcześniejszym opisem powyższego towaru/usługi.',
        ]);
        $invoice_items_corrected[1]->vatRate->name = 'vat_name_k2';
        $invoice_items_corrected[1]->vatRate->save();
        $invoice_tax_reports_corrected = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoice_corrected->id,
        ]);

        $invoice_tax_reports_corrected[0]->vatRate->name = 'vat_name';
        $invoice_tax_reports_corrected[0]->vatRate->save();
        $invoice_tax_reports_corrected[0]->update([
            'price_net' => 80,
            'price_gross' => 180,
        ]);
        $invoice_tax_reports_corrected[1]->vatRate->name = 'vat_name_2';
        $invoice_tax_reports_corrected[1]->vatRate->save();
        $invoice_tax_reports_corrected[0]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);
        $invoice_corrected->paymentMethod->name = 'cash';
        $invoice_corrected->paymentMethod->slug = 'gotowka';
        $invoice_corrected->paymentMethod->save();

        $invoice = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice_corrected->id,
            'correction_type' => InvoiceCorrectionType::TAX,
            'company_id' => $company->id,
            'price_net' => -500,
            'vat_sum' => -1500,
            'price_gross' => -2500,
            'number' => 'corrected_number',
            'drawer_id' => $drawer->id,
        ]);
        $invoice->receipts()->attach($receipts);
        $invoice_corrected->receipts()->attach($receipts);

        $contractor_invoice = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'name' => 'contractor_name',
            'main_address_street' => 'sielska',
            'main_address_number' => '10',
            'main_address_zip_code' => '60-666',
            'main_address_city' => 'poznan',
            'main_address_country' => 'Polska',
            'vatin' => 789456789,
        ]);
        $company_invoice = factory(InvoiceCompany::class)->create([
            'name' => 'company_name',
            'invoice_id' => $invoice->id,
            'main_address_street' => 'sielska_2',
            'main_address_number' => '12',
            'main_address_zip_code' => '60-333',
            'main_address_city' => 'wroclaw',
            'main_address_country' => 'Polska',
            'vatin' => 123456789,
        ]);
        $invoice_items = factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_items[0]->update([
            'name' => 'name_2',
            'price_net' => -100,
            'price_net_sum' => -1000,
            'vat_sum' => -1000,
            'price_gross' => -200,
            'price_gross_sum' => -2000,
            'position_corrected_id' => $invoice_items_corrected[1]->id,
            'print_name' => 'print_name',
            'quantity' => '3000',
        ]);
        $invoice_items[0]->vatRate->name = 'vat_name_a1';
        $invoice_items[0]->vatRate->save();
        $invoice_items[1]->update([
            'name' => 'new_item',
            'price_net' => 1100,
            'price_net_sum' => 11000,
            'vat_sum' => 1000,
            'price_gross' => 1200,
            'price_gross_sum' => 12000,
            'print_name' => 'print_name2',
            'quantity' => '5000',
        ]);
        $invoice_items[1]->vatRate->name = 'vat_name_b1';
        $invoice_items[1]->vatRate->save();
        $invoice_tax_reports = factory(InvoiceTaxReport::class, 2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice_tax_reports[0]->vatRate->name = 'vat_name3';
        $invoice_tax_reports[0]->vatRate->save();
        $invoice_tax_reports[0]->update([
            'price_net' => -80,
            'price_gross' => -180,
        ]);
        $invoice_tax_reports[1]->vatRate->name = 'vat_name_4';
        $invoice_tax_reports[1]->vatRate->save();
        $invoice_tax_reports[1]->update([
            'price_net' => 280,
            'price_gross' => 2180,
        ]);
        $invoice_corrected->paymentMethod->name = 'bank transfer';
        $invoice_corrected->paymentMethod->save();

        $array = [
            'Sprzedawca',
            'Faktura VAT Korekta',
            'company_name',
            'nr',
            'corrected_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Do paragonów:',
            $receipts->pluck('number')->implode(', '),
            'Data wystawienia:',
            $invoice->issue_date,
            'Data korekty:',
            $invoice->sale_date,
            'Do dokumentu nr:',
            'sample_number',
            'Wystawionego w dniu:',
            $invoice_corrected->issue_date,
            'Z datą sprzedaży:',
            $invoice_corrected->issue_date,
            'Nabywca',
            'contractor_name',
            'sielska',
            '10',
            '60-666',
            'poznan',
            'NIP:',
            '789456789',
            'Przed korektą',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            1,
            'name_2',
            10,
            number_format_output(1100),
            number_format_output(11000),
            'vat_name_k2',
            number_format_output(12000),
            'Po korekcie',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            1,
            'name_2',
            3,
            number_format_output(-100),
            number_format_output(-1000),
            'vat_name_a1',
            number_format_output(-2000),
            2,
            'new_item',
            5,
            number_format_output(1100),
            number_format_output(11000),
            'vat_name_b1',
            number_format_output(12000),
            'Forma płatności',
            'Termin',
            'Kwota',
            'Razem',
            number_format_output(-500),
            'X',
            number_format_output(-1500),
            number_format_output(-2500),
            'gotówka',
            $receipts[0]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'w tym',
            number_format_output(-80),
            'vat_name3',
            number_format_output(-100),
            number_format_output(-180),
            'gotówka',
            $receipts[1]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            number_format_output(280),
            'vat_name_4',
            number_format_output(1900),
            number_format_output(2180),
            'gotówka',
            $receipts[2]->sale_date->format('Y-m-d'),
            number_format_output(2000),
            'Razem do zwrotu',
            number_format_output(2500),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT Korekta nr corrected_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_proforma_with_correct_title_and_footer()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $array = [
            'Sprzedawca',
            'Faktura Pro Forma',
            'nr sample_number',
            'Faktura Pro Forma nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_margin_with_annotations()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id,
            'invoice_margin_procedure_id' => InvoiceMarginProcedure::findBySlug(InvoiceMarginProcedureType::ART)->id,
        ]);
        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'nr sample_number',
            'Procedura marży',
            'dzieła sztuki',
            'Lp',
            'Cena netto',
            'Wartość brutto',
            number_format_output(100),
            number_format_output(2000),
            'Razem',
            number_format_output(5600),
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_reverse_charge_with_annotations()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::PREMIUM);
        $invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
                'price_net' => 4300,
                'price_gross' => 4300,
        ]);
        $invoice->taxes()->delete();
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'price_net' => 4300,
            'price_gross' => 4300,
        ]);

        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'nr sample_number',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'VAT',
            'netto', // Cena
            'netto', // Wartość
            'brutto', // Wartość
            number_format_output(100),
            '--- *',
            number_format_output(2000),
            number_format_output(3300),
            '--- *',
            number_format_output(3600),
            'Razem',
            number_format_output(4300),
            '---',
            number_format_output(4300),
            'w tym',
            number_format_output(4300),
            '--- *',
            '---',
            number_format_output(4300),
            'Zapłacono',
            number_format_output(4300),
            '*) - odwrotne obciążenie',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_reverse_charge_correction_with_annotations()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setCorrectionInvoicePrintingEnvironment();

        $invoice_corrected = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
        ]);

        $invoice->update([
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'corrected_invoice_id' => $invoice_corrected->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
        ]);

        $array = [
            'Sprzedawca',
            'Faktura VAT Korekta',
            'nr sample_number',
            'Przed',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'VAT',
            'netto', // Cena
            'netto', // Wartość
            'brutto', // Wartość
            number_format_output(100),
            '--- *',
            number_format_output(2000),
            number_format_output(11000),
            '--- *',
            number_format_output(12000),
            'Po korekcie',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'VAT',
            'netto', // Cena
            'netto', // Wartość
            'brutto', // Wartość
            number_format_output(100),
            '--- *',
            number_format_output(2000),
            number_format_output(3300),
            '--- *',
            number_format_output(3600),
            'Razem',
            number_format_output(4300),
            '---',
            number_format_output(4300),
            'w tym',
            number_format_output(4300),
            '--- *',
            '---',
            number_format_output(4300),
            'Zapłacono',
            number_format_output(4300),
            '*) - odwrotne obciążenie',
            'Faktura VAT Korekta',
            'nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_check_partial_payment_on_invoice_with_bank_transfer()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $invoice->update([
            'payment_method_id' => 2,
            'price_net' => 8000,
            'vat_sum' => 2000,
            'price_gross' => 10000,
            'payment_left' => 1000,
            'payment_term_days' => 14,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
            'amount' => 9000,
            'payment_method_id' => 1,
        ]);

        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'Odbiorca',
            'contractor_name',
            'receiver_name',
            'sielska',
            '10',
            'grunwaldzka',
            '2',
            '60-666',
            'poznan',
            '62-000',
            'Lubon',
            'NIP:',
            '789456789',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Forma płatności',
            'Termin',
            'Kwota',
            'Razem',
            number_format_output(8000),
            'X',
            number_format_output(2000),
            number_format_output(10000),
            'przelew',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            number_format_output(1000),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            'gotówka',
            Carbon::parse($invoice->issue_date)->toDateString(),
            number_format_output(9000),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Bank',
            'Razem do zapłaty',
            number_format_output(10000),
            'zł',
            'Pozostało',
            number_format_output(1000),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_check_partial_payment_on_invoice_with_card()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $invoice->update([
            'payment_method_id' => PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            'price_net' => 8000,
            'vat_sum' => 2000,
            'price_gross' => 10000,
            'payment_left' => 1000,
            'payment_term_days' => 0,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
            'amount' => 9000,
            'payment_method_id' => PaymentMethod::findBySlug(PaymentMethodType::CASH)->id,
        ]);
        factory(InvoicePayment::class)->create([
            'invoice_id' => $invoice->id,
            'special_partial_payment' => true,
            'amount' => 1000,
            'payment_method_id' => PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
        ]);

        $array = [
            'Sprzedawca',
            'Faktura VAT',
            'company_name',
            'nr',
            'sample_number',
            'sielska_2',
            '12',
            '60-333',
            'wroclaw',
            'NIP:',
            '123456789',
            'Data wystawienia:',
            $invoice->issue_date,
            'Data sprzedaży',
            $invoice->sale_date,
            'Nabywca',
            'Odbiorca',
            'contractor_name',
            'receiver_name',
            'sielska',
            '10',
            'grunwaldzka',
            '2',
            '60-666',
            'poznan',
            '62-000',
            'Lubon',
            'NIP:',
            'AF789456789',
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Forma płatności',
            'Termin',
            'Kwota',
            'Razem',
            number_format_output(8000),
            'X',
            number_format_output(2000),
            number_format_output(10000),
            'karta',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            number_format_output(1000),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            'gotówka',
            Carbon::parse($invoice->issue_date)->toDateString(),
            number_format_output(9000),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Zapłacono',
            number_format_output(10000),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_test_long_invoice_will_be_display_properly_on_2_pages_instead_of_3()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $extra_items = factory(InvoiceItem::class, 16)->create(['invoice_id' => $invoice->id]);

        $extra_items->each(function ($item, $key) {
            $item->vatRate->name = 'vat_name_u' . $key;
            $item->vatRate->save();
        });

        // This longer vat name was causing problem
        $extra_items[0]->vatRate->name = 'Miss Joanna d` from France';
        $extra_items[0]->vatRate->save();

        ob_start();

        $array = [
            'Strona (1/2)',
            'Strona (2/2)',
        ];

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $text_content);

            // Test number of pages
            $pages = explode("\f", trim($text_content));
            // here extra one will be calculated although there is none in PDF
            $this->assertSame(2, count($pages) - 1);
            // Count how many footer are in document (counting number of pages in another way)
            $footer_count = mb_substr_count(
                $text_content,
                'Wygenerowano z aplikacji internetowej fvonline.pl '
            );
            $this->assertSame(2, $footer_count);

            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_check_printing_pkwiu_and_service_unit()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);
        $array = [
            'Cena',
            'Wartość',
            'Wartość',
            'Lp',
            'Usługa/Towar',
            'PKWIU',
            'Ilość',
            'J.m.',
            'VAT',
            'netto',
            'netto',
            'brutto',
            '1',
            'custom_name',
            '11.11.11.1',
            '10',
            ServiceUnit::findBySlug(ServiceUnit::SERVICE)->slug,
            number_format_output(100),
            number_format_output(1000),
            'vat_name_a1',
            number_format_output(2000),
            '2',
            'name_2',
            '22.22.22.2',
            '3',
            ServiceUnit::findBySlug('km')->slug,
            number_format_output(1100),
            number_format_output(3300),
            'vat_name_b1',
            number_format_output(3600),
            'Sposób płatności:',
            'gotówka',
            'Termin płatności:',
            Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)
                ->toDateString(),
            'Razem',
            number_format_output(4300),
            'X',
            number_format_output(1300),
            number_format_output(5600),
            'w tym',
            number_format_output(80),
            'vat_name',
            number_format_output(100),
            number_format_output(180),
            number_format_output(280),
            'vat_name_2',
            number_format_output(1900),
            number_format_output(2180),
            'Zapłacono',
            number_format_output(5600),
            'zł',
            'John Doe',
            '.......................................',
            '.......................................',
            'Sprzedawca',
            'Nabywca',
            'Wygenerowano z aplikacji internetowej fvonline.pl',
            'Faktura VAT nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_make_sure_amount_is_handled_in_correct_way_for_small_decimal()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        // for 1st item we save 8,93 as quantity
        $invoice_items = $invoice->items()->get();
        $invoice_items[0]->quantity = 8930;
        $invoice_items[0]->save();

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertStringNotContainsString('893', $text_content);
            $this->assertStringNotContainsString('8 93', $text_content);
            $this->assertStringContainsString('8,93', $text_content);

            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_make_sure_amount_is_handled_in_correct_way_for_big_decimal()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        // for 1st item we save 8,93 as quantity
        $invoice_items = $invoice->items()->get();
        $invoice_items[0]->quantity = 123548930;
        $invoice_items[0]->save();

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertStringNotContainsString('12354893', $text_content);
            $this->assertStringNotContainsString('123 548 93', $text_content);
            $this->assertStringContainsString('123 548,93', $text_content);

            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_make_sure_amount_is_handled_in_correct_way_for_integer()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        // for 1st item we save 8,93 as quantity
        $invoice_items = $invoice->items()->get();
        $invoice_items[0]->quantity = 80000000;
        $invoice_items[0]->save();

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertStringNotContainsString('80000', $text_content);
            $this->assertStringNotContainsString('80 000 000', $text_content);
            $this->assertStringNotContainsString('80,000', $text_content);
            $this->assertStringContainsString('80 000', $text_content);

            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_very_long_description()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        // long description
        $invoice->description = array_reduce(range(0, 100), function ($initial, $item) {
            return $initial .= ' ' . $item;
        }, '');
        $invoice->save();

        $array = [
            'AF789456789',
            'Opis:',
            '1',
            '50',
            '100',
            'Cena',
        ];

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        $this->assertSamePdf($file, $pdf_content, $text_file, $array, $directory);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_not_print_rest_if_no_partial_payments()
    {
        list($directory, $file, $text_file, $company, $invoice) = $this->setInvoicePrintingEnvironment(Package::START);

        $invoice->specialPayments()->delete();

        ob_start();

        $this->get('invoices/' . $invoice->id . '/pdf?selected_company_id=' . $company->id)
            ->assertResponseOk();

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertStringNotContainsString('Pozostało', $text_content);
            File::deleteDirectory($directory);
        }
    }

    /**
     * @param $file
     * @param $pdf_content
     * @param $text_file
     * @param $array
     * @param $directory
     */
    protected function assertSamePdf($file, $pdf_content, $text_file, $array, $directory)
    {
        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            $text_content = file_get_contents($text_file);
            $this->assertContainsOrdered($array, $text_content);
            File::deleteDirectory($directory);
        }
    }
}
