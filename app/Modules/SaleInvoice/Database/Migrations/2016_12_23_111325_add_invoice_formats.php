<?php

use App\Models\Db\InvoiceFormat;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceFormats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoice_formats_data = [
            [
                'name' => 'numer w miesiącu / miesiąc / rok',
                'format' => '{%nr}/{%m}/{%Y}',
                'example' => '1011/11/2017',
            ],
            [
                'name' => 'miesiąc / numer w miesiącu / rok',
                'format' => '{%m}/{%nr}/{%Y}',
                'example' => '11/1011/2017',
            ],
        ];

        foreach ($invoice_formats_data as $item) {
            $invoice_format = new InvoiceFormat();
            $invoice_format->name = $item['name'];
            $invoice_format->format = $item['format'];
            $invoice_format->example = $item['example'];
            $invoice_format->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        InvoiceFormat::truncate();
    }
}
