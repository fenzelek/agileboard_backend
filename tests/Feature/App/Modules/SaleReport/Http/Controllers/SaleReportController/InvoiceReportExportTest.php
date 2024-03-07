<?php

namespace Tests\Feature\App\Modules\SaleReport\Http\Controllers\SaleReportController;

use App\Helpers\ErrorCode;
use App\Models\Db\CompanyService;
use App\Models\Db\Contractor;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Db\InvoiceType;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Models\Db\VatRate;
use App\Models\Other\ModuleType;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\RoleType;
use App\Modules\SaleReport\Services\Contracts\ExternalExportProvider;
use App\Modules\SaleReport\Services\ExternalReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use File;
use Mockery;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class InvoiceReportExportTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    protected $company;
    protected $invoice;
    protected $now_string;

    public function setUp():void
    {
        parent::setUp();
        $now = Carbon::create(2017, 1, 1);
        $this->now_string = $now->toDateTimeString();
        Carbon::setTestNow($now);

        $this->createUser();
        $this->be($this->user);
        $this->company = $this->createCompanyWithRoleAndPackage(RoleType::TAX_OFFICE, Package::PREMIUM);

        $this->invoice = $this->prepareEnvironment();
    }

    /**
     * @test
     */
    public function invoicesRegisterExport_user_has_permission()
    {
        $module = Module::where('slug', ModuleType::INVOICES_REGISTER_EXPORT_NAME)->first();
        $this->company->companyModules()->where('module_id', $module->id)->update(['value' => 'firmen']);

        ob_start();
        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id)
            ->assertResponseOk();
        ob_end_clean();
    }

    /** @test */
    public function invoicesRegisterExport_get_proper_data()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/test.csv');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }

        $module = Module::where('slug', ModuleType::INVOICES_REGISTER_EXPORT_NAME)->first();
        $this->company->companyModules()->where('module_id', $module->id)->update(['value' => 'firmen']);

        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $data = $this->response->getContent();

        $template = $this->getTemplateArray();

        // Invoice 2/01/2017
        $row = $template;
        $row['K_KONTRAH'] = 'Some company';
        $row['K_ULICA'] = 'Some street 1';
        $row['K_KOD'] = '11-111';
        $row['K_MIEJSCE'] = 'City';
        $row['K_NUMERNIP'] = 'AF123';
        $row['NRDOK'] = '2/01/2017';
        $row['DATA'] = '20170101';
        $row['WARTBRUTTO'] = '12.30';
        $row['NET7'] = '1.00';
        $row['VAT7'] = '0.08';
        $row['SPKRAJ0'] = '1.00';
        $row['NET12'] = '1.00';
        $row['VAT12'] = '0.05';
        $row['SPWOLNA'] = '1.00';
        $row['VATSPRZ'] = '2.30';
        $row['SPZAP'] = '3';
        $row['ZAPLAC'] = '12.30';
        $row['RODZDOK'] = '1';
        $row['DATASP'] = '20170101';
        $row['DATATERMIN'] = '20170115';
        $csv_array[0] = $row;

        // Invoice 3/01/2017
        $row = $template;
        $row['K_KONTRAH'] = 'Some company';
        $row['K_ULICA'] = 'Some street 1';
        $row['K_KOD'] = '11-111';
        $row['K_MIEJSCE'] = 'City';
        $row['K_NUMERNIP'] = 'AF123';
        $row['NRDOK'] = '3/01/2017';
        $row['DATA'] = '20170101';
        $row['WARTBRUTTO'] = '12.30';
        $row['SPZAP'] = '3';
        $row['ZAPLAC'] = '12.30';
        $row['RODZDOK'] = 'G';
        $row['DATASP'] = '20170101';
        $row['DATATERMIN'] = '20170108';
        $row['NETTOTOW'] = '120.00';
        $row['NETTOUSL'] = '70.00';
        $csv_array[1] = $row;

        // Invoice TEST/4/01/2017
        $row = $template;
        $row['K_KONTRAH'] = 'Some company';
        $row['K_ULICA'] = 'Some street 1';
        $row['K_KOD'] = '11-111';
        $row['K_MIEJSCE'] = 'City';
        $row['K_NUMERNIP'] = 'AF123';
        $row['NRDOK'] = 'TEST/4/01/2017';
        $row['DATA'] = '20170101';
        $row['SPZAP'] = '3';
        $row['ZAPLAC'] = '195.00';
        $row['RODZDOK'] = 'H';
        $row['DATASP'] = '20170101';
        $row['DATATERMIN'] = '20170108';
        $row['NRDOKORG'] = '3/01/2017';
        $row['DATAORG'] = '20170101';
        $row['ZMCENA'] = '195.00';
        $row['ZMPODAT'] = '0.00';
        $row['TYTULKOR'] = 'Korekta wartości/ceny';
        $row['NETTOTOW'] = '320.00';
        $row['NETTOUSL'] = '70.00';
        $csv_array[2] = $row;

        // Invoice 1/01/2017
        $row = $template;
        $row['K_KONTRAH'] = 'I am a long company name. Longer then 56 characters. Bes';
        $row['K_KONTRAH1'] = 't company ever. Ążźśęćńół.';
        $row['K_ULICA'] = 'Some street 1';
        $row['K_KOD'] = '11-111';
        $row['K_MIEJSCE'] = 'City';
        $row['K_NUMERNIP'] = 'AF123';
        $row['NRDOK'] = '1/01/2017';
        $row['DATA'] = '20170102';
        $row['WARTBRUTTO'] = '12.30';
        $row['NET22'] = '1.00';
        $row['VAT22'] = '0.23';
        $row['NET7'] = '1.00';
        $row['VAT7'] = '0.08';
        $row['EKSP0'] = '3.33';
        $row['SPKRAJ0'] = '1.00';
        $row['NET12'] = '1.00';
        $row['VAT12'] = '0.05';
        $row['BEZ_VAT'] = '272.00';
        $row['SPWOLNA'] = '1.00';
        $row['VATSPRZ'] = '2.30';
        $row['SPZAP'] = '3';
        $row['ZAPLAC'] = '12.30';
        $row['RODZDOK'] = '1';
        $row['DATASP'] = '20170101';
        $row['DATATERMIN'] = '20170109';
        $csv_array[3] = $row;

        foreach ($csv_array as $key => $row) {
            $csv_array[$key] = implode(';', $row);
        }

        // Check headers
        $headers = $this->response->headers->all();
        $this->assertEquals('text/csv; charset=UTF-8', $headers['content-type'][0]);
        $this->assertEquals(
            'attachment; filename=firmen2017-01-01.csv',
            $headers['content-disposition'][0]
        );

        // Test file content
        File::put($file, $data);
        $text_content = file_get_contents($file);
        $text_content = mb_convert_encoding($text_content, 'UTF-8', 'ISO-8859-2');
        // Test number of rows
        $lines = explode(PHP_EOL, $text_content);
        $this->assertCount(4, $lines);
        // Test content
        foreach ($csv_array as $key => $row) {
            $this->assertEquals($row, trim($lines[$key]));
        }
        File::deleteDirectory($directory);
    }

    /** @test */
    public function invoicesRegisterExport_check_vat_filter()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/test.csv');

        // set up directory and files and make sure files don't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0777);
        }
        if (File::exists($file)) {
            File::delete($file);
        }

        $module = Module::where('slug', ModuleType::INVOICES_REGISTER_EXPORT_NAME)->first();
        $this->company->companyModules()->where('module_id', $module->id)->update(['value' => 'firmen']);

        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id
            . '&vat_rate_id=1')
            ->assertResponseOk();

        $data = $this->response->getContent();

        $template = $this->getTemplateArray();

        // Invoice 1/01/2017
        $row = $template;
        $row['K_KONTRAH'] = 'I am a long company name. Longer then 56 characters. Bes';
        $row['K_KONTRAH1'] = 't company ever. Ążźśęćńół.';
        $row['K_ULICA'] = 'Some street 1';
        $row['K_KOD'] = '11-111';
        $row['K_MIEJSCE'] = 'City';
        $row['K_NUMERNIP'] = 'AF123';
        $row['NRDOK'] = '1/01/2017';
        $row['DATA'] = '20170102';
        $row['WARTBRUTTO'] = '12.30';
        $row['NET22'] = '1.00';
        $row['VAT22'] = '0.23';
        $row['VATSPRZ'] = '2.30';
        $row['SPZAP'] = '3';
        $row['ZAPLAC'] = '12.30';
        $row['RODZDOK'] = '1';
        $row['DATASP'] = '20170101';
        $row['DATATERMIN'] = '20170109';
        $csv_array[0] = $row;

        foreach ($csv_array as $key => $row) {
            $csv_array[$key] = implode(';', $row);
        }

        // Test file content
        File::put($file, $data);
        $text_content = file_get_contents($file);
        $text_content = mb_convert_encoding($text_content, 'UTF-8', 'ISO-8859-2');
        // Test number of rows
        $lines = explode(PHP_EOL, $text_content);
        $this->assertCount(1, $lines);
        // Test content
        foreach ($csv_array as $key => $row) {
            $this->assertEquals($row, trim($lines[$key]));
        }
        File::deleteDirectory($directory);
    }

    /**
     * @test
     */
    public function invoicesRegisterExport_wrong_application_setting_will_throw_500_error()
    {
        $module = Module::where('slug', ModuleType::INVOICES_REGISTER_EXPORT_NAME)->first();
        $this->company->companyModules()->where('module_id', $module->id)->update(['value' => 'I dont exists']);

        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id
            . '&vat_rate_id=1')
            ->assertResponseStatus(500);
    }

    /**
     * @test
     */
    public function invoicesRegisterExport_no_name_in_application_setting_will_throw_custom_error()
    {
        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id
            . '&vat_rate_id=1')
            ->assertResponseStatus(426);

        $this->verifyErrorResponse(426, ErrorCode::PACKAGE_CANT_USE_CUSTOM_EXPORTS);
    }

    /** @test */
    public function invoicesRegisterExport_it_runs_required_service_methods_and_returns_expected_data()
    {
        $this->withoutExceptionHandling();
        $content = 'sample content';
        $content_type = 'my/content-type';
        $filename = 'custom-file-name.abc';
        $invoices = new \Illuminate\Database\Eloquent\Collection('sample invoices');

        $provider = Mockery::mock(ExternalExportProvider::class);
        $provider->shouldReceive('getFileContent')->once()->with($invoices)->andReturn($content);
        $provider->shouldReceive('getFileContentType')->once()->withNoArgs()->andReturn($content_type);
        $provider->shouldReceive('getFileName')->once()->withNoArgs()->andReturn($filename);

        $external_report = Mockery::mock(ExternalReport::class);
        $external_report->shouldReceive('getProvider')->once()->andReturn($provider);
        $external_report->shouldReceive('getInvoices')->once()->andReturn($invoices);

        app()->instance(ExternalReport::class, $external_report);
        $module = Module::where('slug', ModuleType::INVOICES_REGISTER_EXPORT_NAME)->first();
        $this->company->companyModules()->where('module_id', $module->id)->update(['value' => 'optima']);

        $this->get('/reports/invoices-report-export?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $this->assertSame($content, $this->response->getContent());
        $headers = $this->response->headers->all();
        $this->assertSame($content_type, $headers['content-type'][0]);
        $this->assertSame('attachment; filename=' . $filename, $headers['content-disposition'][0]);
    }

    protected function prepareEnvironment()
    {
        $contractor = factory(Contractor::class)->create([
            'name' => 'Contractor',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
            'country_vatin_prefix_id' => 1,
        ]);
        $invoice = factory(Invoice::class)->create([
            'number' => '1/01/2017',
            'company_id' => $this->company->id,
            'contractor_id' => $contractor->id,
            'issue_date' => Carbon::tomorrow()->toDateTimeString(),
            'sale_date' => $this->now_string,
            'payment_term_days' => 7,
            'price_gross' => 1230,
            'price_net' => 1000,
            'vat_sum' => 230,
            'payment_left' => 1230,
        ]);
        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
            'contractor_id' => $contractor->id,
            'name' => 'I am a long company name. Longer then 56 characters. Best company ever. Ążźśęćńół.',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
            'country_vatin_prefix_id' => 1,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 600,
            'price_net' => 600,
            'quantity' => 10,
            'price_net_sum' => 6000,
            'price_gross_sum' => 6000,
            'type' => CompanyService::TYPE_ARTICLE,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 700,
            'price_net' => 700,
            'quantity' => 5,
            'price_net_sum' => 3500,
            'price_gross_sum' => 3500,
            'type' => CompanyService::TYPE_SERVICE,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('23%')->id,
            'price_gross' => 123,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('8%')->id,
            'price_gross' => 108,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('0%')->id,
            'price_gross' => 100,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 7300,
            'price_net' => 7300,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('np. UE')->id,
            'price_gross' => 19900,
            'price_net' => 19900,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('0% EXP')->id,
            'price_gross' => 222,
            'price_net' => 222,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('0% WDT')->id,
            'price_gross' => 111,
            'price_net' => 111,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('5%')->id,
            'price_gross' => 105,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice->id,
            'vat_rate_id' => VatRate::findByName('zw.')->id,
            'price_gross' => 100,
            'price_net' => 100,
        ]);

        $contractor2 = factory(Contractor::class)->create([
            'name' => 'Contractor 2',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
        ]);

        // invoice without 23% vat
        $invoice2 = factory(Invoice::class)->create([
            'number' => '2/01/2017',
            'company_id' => $this->company->id,
            'contractor_id' => $contractor2->id,
            'issue_date' => $this->now_string,
            'sale_date' => $this->now_string,
            'payment_term_days' => 14,
            'price_gross' => 1230,
            'price_net' => 1000,
            'vat_sum' => 230,
            'payment_left' => 1230,
        ]);
        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice2->id,
            'contractor_id' => $contractor2->id,
            'name' => 'Some company',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
            'country_vatin_prefix_id' => 1,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice2->id,
            'vat_rate_id' => VatRate::findByName('8%')->id,
            'price_gross' => 108,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice2->id,
            'vat_rate_id' => VatRate::findByName('0%')->id,
            'price_gross' => 100,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice2->id,
            'vat_rate_id' => VatRate::findByName('5%')->id,
            'price_gross' => 105,
            'price_net' => 100,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice2->id,
            'vat_rate_id' => VatRate::findByName('zw.')->id,
            'price_gross' => 100,
            'price_net' => 100,
        ]);

        // REVERSE CHARGE
        $invoice3 = factory(Invoice::class)->create([
            'number' => '3/01/2017',
            'company_id' => $this->company->id,
            'contractor_id' => $contractor->id,
            'issue_date' => $this->now_string,
            'sale_date' => $this->now_string,
            'payment_term_days' => 7,
            'price_gross' => 1230,
            'price_net' => 1000,
            'vat_sum' => 0,
            'payment_left' => 1230,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
        ]);
        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice3->id,
            'contractor_id' => $contractor->id,
            'name' => 'Some company',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
            'country_vatin_prefix_id' => 1,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice3->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 600,
            'price_net' => 600,
            'quantity' => 10,
            'price_net_sum' => 6000,
            'price_gross_sum' => 6000,
            'type' => CompanyService::TYPE_ARTICLE,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice3->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 700,
            'price_net' => 700,
            'quantity' => 5,
            'price_net_sum' => 3500,
            'price_gross_sum' => 3500,
            'type' => CompanyService::TYPE_SERVICE,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice3->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 6000,
            'price_net' => 6000,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice3->id,
            'vat_rate_id' => VatRate::findByName('np. UE')->id,
            'price_gross' => 3500,
            'price_net' => 3500,
        ]);

        // REVERSE CHARGE CORRECTION
        $invoice4 = factory(Invoice::class)->create([
            'number' => 'TEST/4/01/2017',
            'company_id' => $this->company->id,
            'contractor_id' => $contractor->id,
            'issue_date' => $this->now_string,
            'sale_date' => $this->now_string,
            'payment_term_days' => 7,
            'price_gross' => 19500,
            'price_net' => 19500,
            'vat_sum' => 0,
            'payment_left' => 19500,
            'invoice_type_id' => InvoiceType::findBySlug(
                InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION
            )->id,
            'corrected_invoice_id' => $invoice3->id,
            'correction_type' => InvoiceCorrectionType::PRICE,

        ]);
        factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice4->id,
            'contractor_id' => $contractor->id,
            'name' => 'Some company',
            'main_address_street' => 'Some street',
            'main_address_number' => 1,
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'City',
            'vatin' => 123,
            'country_vatin_prefix_id' => 1,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice4->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 600,
            'price_net' => 600,
            'quantity' => 100,
            'price_net_sum' => 16000,
            'price_gross_sum' => 16000,
            'type' => CompanyService::TYPE_ARTICLE,
        ]);
        factory(InvoiceItem::class, 2)->create([
            'invoice_id' => $invoice4->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 700,
            'price_net' => 700,
            'quantity' => 5,
            'price_net_sum' => 3500,
            'price_gross_sum' => 3500,
            'type' => CompanyService::TYPE_SERVICE,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice4->id,
            'vat_rate_id' => VatRate::findByName('np.')->id,
            'price_gross' => 6000,
            'price_net' => 6000,
        ]);
        factory(InvoiceTaxReport::class)->create([
            'invoice_id' => $invoice4->id,
            'vat_rate_id' => VatRate::findByName('np. UE')->id,
            'price_gross' => 3500,
            'price_net' => 3500,
        ]);

        return $invoice;
    }

    /**
     * Returns array of Firmen structure of the row in csv file.
     *
     * @return array
     */
    protected function getTemplateArray()
    {
        return array_fill_keys([
            'K_KONTRAH',
            'K_KONTRAH1',
            'K_ULICA',
            'K_KOD',
            'K_MIEJSCE',
            'K_NUMERNIP',
            'NRDOK',
            'DATA',
            'WARTBRUTTO',
            'NET22',
            'VAT22',
            'NET7',
            'VAT7',
            'EKSP0',
            'SPKRAJ0',
            'NET12',
            'VAT12',
            'BEZ_VAT',
            'SPWOLNA',
            'VATSPRZ',
            'SPZAP',
            'DATAZAP',
            'ZAPLAC',
            'RODZDOK',
            'DATASP',
            'DATAOPOD',
            'DATATERMIN',
            'ZALICZKA',
            'STAWKA_Z',
            'NRDOKORG',
            'DATAORG',
            'ZMCENA',
            'ZMPODAT',
            'TYTULKOR',
            'ZMZWOL',
            'NETTOTOW',
            'NETTOUSL',
            'Rezerwa 1',
            'Rezerwa 2',
            'Rezerwa 3',
        ], '');
    }
}
