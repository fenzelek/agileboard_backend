<?php

use Illuminate\Database\Migrations\Migration;

class InvoiceFooterSetHidden extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Db\Module::where('slug', 'invoices.footer.enabled')->update(['visible' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
