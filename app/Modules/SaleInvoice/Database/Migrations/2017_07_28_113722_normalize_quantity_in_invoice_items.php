<?php

use App\Models\Db\InvoiceItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NormalizeQuantityInInvoiceItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach (InvoiceItem::all() as $item) {
                $item->update(['quantity' => normalize_quantity($item->quantity)]);
            }
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
            foreach (InvoiceItem::all() as $item) {
                $item->update(['quantity' => denormalize_quantity($item->quantity)]);
            }
        });
    }
}
