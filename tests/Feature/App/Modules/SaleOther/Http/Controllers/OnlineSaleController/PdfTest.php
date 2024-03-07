<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\OnlineSaleController;

use App\Models\Db\Company;
use App\Models\Db\OnlineSale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use File;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\StringHelper;

class PdfTest extends BrowserKitTestCase
{
    use DatabaseTransactions, StringHelper;

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_params()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/online-sale.pdf');
        $text_file = storage_path('tests/online-sale.txt');

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
        $online_sales = factory(OnlineSale::class, 2)->create(['company_id' => $company->id]);
        $this->normalizeDataForPdf($company, $online_sales);
        $array = [
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Łączna kwota brutto:',
            '0zł',
            'Parametry',
            'Okres:',
            'od',
            '2017-01-01',
            'do',
            '2017-01-15',
            'Numer:',
            '333',
            'Numer transakcji:',
            '222222',
            'Email:',
            'test@test.pl',
            'Lp.',
            'Nr',
            'Nr transakcji',
            'Email',
            'Utworzono',
            'Kwota netto',
            'Kwota brutto',
            'VAT',
        ];

        ob_start();
        $this->get('/online-sales/pdf?selected_company_id=' . $company->id .
            '&date_start=2017-01-01&date_end=2017-01-15&transaction_number=222222&number=333&email=test@test.pl')
            ->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
            File::deleteDirectory($directory);
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_success_table()
    {
        // set directory and file names
        $directory = storage_path('tests');
        $file = storage_path('tests/online-sale.pdf');
        $text_file = storage_path('tests/online-sale.txt');

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
        $online_sales = factory(OnlineSale::class, 2)->create(['company_id' => $company->id]);
        $this->normalizeDataForPdf($company, $online_sales);

        $array = [
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Łączna kwota brutto:',
            denormalize_price($online_sales->sum('price_gross')) . 'zł',
            'Lp.',
            'Nr',
            'Nr transakcji',
            'Email',
            'Utworzono',
            'Kwota netto',
            'Kwota brutto',
            'VAT',
            //row 1
            1,
            $online_sales[0]->number,
            $online_sales[0]->transaction_number,
            $online_sales[0]->email,
            $online_sales[0]->sale_date->format('Y-m-d H:i:s'),
            denormalize_price($online_sales[0]->price_net) . 'zł',
            denormalize_price($online_sales[0]->price_gross) . 'zł',
            denormalize_price($online_sales[0]->vat_sum) . 'zł',
            //row2
            2,
            $online_sales[1]->number,
            $online_sales[1]->transaction_number,
            $online_sales[1]->email,
            $online_sales[1]->sale_date->format('Y-m-d H:i:s'),
            denormalize_price($online_sales[1]->price_net) . 'zł',
            denormalize_price($online_sales[1]->price_gross) . 'zł',
            denormalize_price($online_sales[1]->vat_sum) . 'zł',
        ];

        ob_start();
        $this->get('/online-sales/pdf?selected_company_id=' . $company->id)->seeStatusCode(200);

        $pdf_content = ob_get_clean();

        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($file, $pdf_content);
            // convert PDF to text file
            exec('pdftotext -layout "' . $file . '" "' . $text_file . '"');
            // here you can test exact pdf content
            $this->assertContainsOrdered($array, file_get_contents($text_file));
            // after tests you can remove the test directory
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

    /**
     * Normalize data. This is done only for pdftotext to resolve problem with some characters be
     * converted into ligatures (for example ff).
     *
     * @param Company $company
     * @param Collection $online_sales
     */
    protected function normalizeDataForPdf(Company $company, Collection $online_sales)
    {
        $this->user->first_name = 'Marcin';
        $this->user->last_name = 'Iksinski';
        $this->user->save();

        $company->name = 'Sample company name';
        $company->save();

        foreach ($online_sales as $k => $v) {
            $online_sales[$k]->number = mt_rand(2, 300) . ' Number for ' . $k;
            $online_sales[$k]->transaction_number = mt_rand(2, 300) . ' trans nr for ' . $k;
            $online_sales[$k]->email = 'sample' . $k . '@example.com';
            $online_sales[$k]->save();
        }
    }
}
