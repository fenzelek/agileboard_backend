<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use App\Models\Db\Company;
use App\Models\Db\ReceiptItem;
use Carbon\Carbon;
use App\Models\Db\Receipt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\PaymentMethod;
use Illuminate\Support\Collection;
use Tests\Helpers\StringHelper;
use File;

class PdfReportTest extends ReceiptController
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
        $file = storage_path('tests/receipts_report.pdf');
        $text_file = storage_path('tests/receipts_report.txt');

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
            'Lista paragonów - Podsumowanie zbiorcze',
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
            'Nazwa',
            'Cena',
            'Suma netto',
            'Suma brutto',
            'Stawka VAT',
        ];

        ob_start();
        $this->get('/receipts/report?selected_company_id=' . $company->id .
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
        $file = storage_path('tests/receipts_report.pdf');
        $text_file = storage_path('tests/receipts_report.txt');

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

        $receipts = $this->prepareData($company);

        $this->normalizeDataForPdf($company, $receipts);

        $expected_results = $this->getExpectedResults();

        $array = [
            'Lista paragonów - Podsumowanie zbiorcze',
            'Firma:',
            $company->name,
            'Data wygenerowania:',
            Carbon::now()->toDateTimeString(),
            'Łączna kwota brutto:',
            denormalize_price($expected_results->sum('price_gross_sum')) . 'zł',
            'Lp.',
            'Nazwa',
            'Cena',
            'Suma netto',
            'Suma brutto',
            'Stawka VAT',
        ];
        // now we add each expected position to array
        $expected_results->each(function ($item, $key) use (&$array) {
            array_push(
                $array,
                $key + 1,
                $item['name'],
                denormalize_price($item['price_gross']) . 'zł',
                $item['quantity'],
                denormalize_price($item['price_net_sum']) . 'zł',
                denormalize_price($item['price_gross_sum']) . 'zł',
                $item['vat_rate']
            );
        });

        ob_start();
        $this->get('/receipts/report?selected_company_id=' . $company->id)->seeStatusCode(200);

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
     * Prepare data in database.
     *
     * @param Company $company
     *
     * @return mixed
     */
    protected function prepareData(Company $company)
    {
        // create receipts for this and other company
        $receipts = factory(Receipt::class, 4)->create(['company_id' => $company->id]);
        $receipts_other_company = factory(Receipt::class, 4)
            ->create(['company_id' => $company->id + 2]);

        $receipt_items = collect($this->getReceiptItemsData());

        // other receipt items for receipts of this and other company
        $this->addReceiptItems($receipts, $receipt_items);
        $this->addReceiptItems($receipts_other_company, $receipt_items);

        return $receipts;
    }

    /**
     * Add items for each given receipts.
     *
     * @param Collection $receipts
     * @param Collection $receipt_items
     */
    protected function addReceiptItems(Collection $receipts, Collection $receipt_items)
    {
        $receipts->each(function ($receipt, $key) use ($receipt_items) {
            $items = collect();
            collect($receipt_items[$key])->each(function ($receipt_item) use ($items) {
                $items->push(factory(ReceiptItem::class)->make($receipt_item));
            });
            $receipt->items()->saveMany($items);
        });
    }

    /**
     * Get expected results in report table.
     *
     * @return Collection
     */
    protected function getExpectedResults()
    {
        return collect([
            [
                'name' => 'Abc test',
                'vat_rate' => '8%',
                'price_gross' => 154,
                'quantity' => 2 + 9,
                'price_net_sum' => 231 + 1512,
                'price_gross_sum' => 308 + 1801,
            ],
            [
                'name' => 'Brand new test',
                'vat_rate' => '23%',
                'price_gross' => 28,
                'quantity' => 1 + 4,
                'price_net_sum' => 23 + 191,
                'price_gross_sum' => 28 + 2412,
            ],
            [
                'name' => 'Brand new test',
                'vat_rate' => '23%',
                'price_gross' => 27,
                'quantity' => 1 + 15,
                'price_net_sum' => 22 + 3512,
                'price_gross_sum' => 27 + 6266,
            ],
            [
                'name' => 'Brand new test',
                'vat_rate' => '8%',
                'price_gross' => 22,
                'quantity' => 13,
                'price_net_sum' => 98012,
                'price_gross_sum' => 1823,
            ],
            [
                'name' => 'Brand new test',
                'vat_rate' => '8%',
                'price_gross' => 21,
                'quantity' => 3,
                'price_net_sum' => 52,
                'price_gross_sum' => 63,
            ],
            [
                'name' => 'Completely new',
                'vat_rate' => '100%',
                'price_gross' => 222,
                'quantity' => 2,
                'price_net_sum' => 221,
                'price_gross_sum' => 444,
            ],
            [
                'name' => 'Test',
                'vat_rate' => '8%',
                'price_gross' => 221,
                'quantity' => 3,
                'price_net_sum' => 512,
                'price_gross_sum' => 663,
            ],
            [
                'name' => 'Test',
                'vat_rate' => '8%',
                'price_gross' => 151,
                'quantity' => 4 + 192,
                'price_net_sum' => 470 + 28931,
                'price_gross_sum' => 604 + 182412,
            ],
        ]);
    }

    /**
     * Get receipt items that will be later saved to receipts.
     *
     * @return array
     */
    protected function getReceiptItemsData()
    {
        return [
            // items for 1st receipt
            [
                [
                    'name' => 'Test',
                    'vat_rate' => '8%',
                    'price_gross' => 221,
                    'quantity' => 3,
                    'price_net_sum' => 512,
                    'price_gross_sum' => 663,
                ],
                [
                    'name' => 'Abc test',
                    'vat_rate' => '8%',
                    'price_gross' => 154,
                    'quantity' => 2,
                    'price_net_sum' => 231,
                    'price_gross_sum' => 308,
                ],
            ],
            // items for 2nd receipt
            [
                [
                    'name' => 'Test',
                    'vat_rate' => '8%',
                    'price_gross' => 151,
                    'quantity' => 4,
                    'price_net_sum' => 470,
                    'price_gross_sum' => 604,
                ],
                [
                    'name' => 'Abc test',
                    'vat_rate' => '8%',
                    'price_gross' => 154,
                    'quantity' => 9,
                    'price_net_sum' => 1512,
                    'price_gross_sum' => 1801,
                ],
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '8%',
                    'price_gross' => 21,
                    'quantity' => 3,
                    'price_net_sum' => 52,
                    'price_gross_sum' => 63,
                ],
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '23%',
                    'price_gross' => 27,
                    'quantity' => 1,
                    'price_net_sum' => 22,
                    'price_gross_sum' => 27,
                ],
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '23%',
                    'price_gross' => 28,
                    'quantity' => 1,
                    'price_net_sum' => 23,
                    'price_gross_sum' => 28,
                ],
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '23%',
                    'price_gross' => 28,
                    'quantity' => 4,
                    'price_net_sum' => 191,
                    'price_gross_sum' => 2412,
                ],
            ],
            // items for 3rd receipt
            [
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '23%',
                    'price_gross' => 27,
                    'quantity' => 15,
                    'price_net_sum' => 3512,
                    'price_gross_sum' => 6266,
                ],
                [
                    'name' => 'Brand new test',
                    'vat_rate' => '8%',
                    'price_gross' => 22,
                    'quantity' => 13,
                    'price_net_sum' => 98012,
                    'price_gross_sum' => 1823,
                ],
            ],
            // items for 4th receipt
            [
                [
                    'name' => 'Completely new',
                    'vat_rate' => '100%',
                    'price_gross' => 222,
                    'quantity' => 2,
                    'price_net_sum' => 221,
                    'price_gross_sum' => 444,
                ],
                [
                    'name' => 'Test',
                    'vat_rate' => '8%',
                    'price_gross' => 151,
                    'quantity' => 192,
                    'price_net_sum' => 28931,
                    'price_gross_sum' => 182412,
                ],
            ],
        ];
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
