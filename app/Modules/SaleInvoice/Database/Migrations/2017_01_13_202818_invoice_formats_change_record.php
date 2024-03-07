<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\InvoiceFormat;

class InvoiceFormatsChangeRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            InvoiceFormat::where('format', '{%m}/{%nr}/{%Y}')->delete();
            InvoiceFormat::create([
                'name' => 'numer w roku / rok',
                'format' => '{%nr}/{%Y}',
                'example' => '100/2017',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            InvoiceFormat::where('format', '{%nr}/{%Y}')->delete();
            InvoiceFormat::create([
                'name' => 'miesiąc / numer w miesiącu / rok',
                'format' => '{%m}/{%nr}/{%Y}',
                'example' => '11/1011/2017',
            ]);
        });
    }
}
