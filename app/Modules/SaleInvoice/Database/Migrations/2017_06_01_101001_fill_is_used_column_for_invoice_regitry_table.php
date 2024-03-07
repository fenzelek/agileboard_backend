<?php

use App\Models\Db\InvoiceRegistry;
use Illuminate\Database\Migrations\Migration;

class FillIsUsedColumnForInvoiceRegitryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            InvoiceRegistry::all()->each(function ($registry) {
                if ($registry->invoices()->first()) {
                    $registry->is_used = true;
                    $registry->save();
                }
            });
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            InvoiceRegistry::all()->each(function ($registry) {
                $registry->is_used = false;
                $registry->save();
            });
        });
    }
}
