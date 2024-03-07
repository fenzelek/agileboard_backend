<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use App\Models\Db\Company;
use Carbon\Carbon;
use App\Models\Db\Receipt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\PaymentMethod;
use Illuminate\Support\Collection;
use Tests\Helpers\StringHelper;
use File;

class PdfTest extends ReceiptController
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
        $file = storage_path('tests/receipts.pdf');
        $text_file = storage_path('tests/receipts.txt');

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
        $company->name = 'ff';
        $company->save();

        $receipts = factory(Receipt::class, 2)->create([
            'company_id' => $company->id,
            'user_id' => $this->user->id,
        ]);
        $this->normalizeDataForPdf($company, $receipts);

        $method = factory(PaymentMethod::class)->create(['name' => 'abcdef']);

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
            'Metoda płatności:',
            $method->name,
            'Numer:',
            '333',
            'Numer transakcji:',
            '222222',
            'Wystawiający:',
            $this->user->first_name . ' ' . $this->user->last_name,
            'Lp.',
            'Nr',
            'Nr transakcji',
            'Utworzono',
            'Kwota netto',
            'Kwota brutto',
            'VAT',
            'Metoda',
        ];

        ob_start();
        $this->get('/receipts/pdf?selected_company_id=' . $company->id .
            '&date_start=2017-01-01&date_end=2017-01-15&transaction_number=222222&number=333&user_id=' .
            $this->user->id . '&payment_method_id=' . $method->id)
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
        $file = storage_path('tests/receipt.pdf');
        $text_file = storage_path('tests/receipt.txt');

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
        $receipts = factory(Receipt::class, 2)->create(['company_id' => $company->id]);
        $this->normalizeDataForPdf($company, $receipts);

        $array = [
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Łączna kwota brutto:',
            denormalize_price($receipts->sum('price_gross')) . 'zł',
            'Lp.',
            'Nr',
            'Nr transakcji',
            'Utworzono',
            'Kwota netto',
            'Kwota brutto',
            'VAT',
            'Metoda',
            //row 1
            1,
            $receipts[0]->number,
            $receipts[0]->transaction_number,
            $receipts[0]->sale_date->format('Y-m-d H:i:s'),
            denormalize_price($receipts[0]->price_net) . 'zł',
            denormalize_price($receipts[0]->price_gross) . 'zł',
            denormalize_price($receipts[0]->vat_sum) . 'zł',
            $receipts[0]->paymentMethod->name,
            //row2
            2,
            $receipts[1]->number,
            $receipts[1]->transaction_number,
            $receipts[1]->sale_date->format('Y-m-d H:i:s'),
            denormalize_price($receipts[1]->price_net) . 'zł',
            denormalize_price($receipts[1]->price_gross) . 'zł',
            denormalize_price($receipts[1]->vat_sum) . 'zł',
            $receipts[1]->paymentMethod->name,
        ];

        ob_start();
        $this->get('/receipts/pdf?selected_company_id=' . $company->id)->seeStatusCode(200);

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
     * Normalize data. This is done only for pdftotext to resolve problem with some characters be
     * converted into ligatures (for example ff).
     *
     * @param Company $company
     * @param Collection $receipts
     */
    protected function normalizeDataForPdf(Company $company, Collection $receipts)
    {
        $this->user->first_name = 'Marcin';
        $this->user->last_name = 'Iksinski';
        $this->user->save();

        $company->name = 'Sample company name';
        $company->save();

        foreach ($receipts as $k => $v) {
            $receipts[$k]->number = mt_rand(2, 300) . ' Number for ' . $k;
            $receipts[$k]->transaction_number = mt_rand(2, 300) . ' trans number for ' . $k;
            $receipts[$k]->save();

            $receipts[$k]->paymentMethod->name = 'method for ' . $k . ' to pay';
            $receipts[$k]->paymentMethod->save();
        }
    }
}
