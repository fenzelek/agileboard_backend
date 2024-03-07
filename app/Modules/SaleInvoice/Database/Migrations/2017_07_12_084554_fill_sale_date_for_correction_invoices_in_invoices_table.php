<?php

use App\Models\Db\Invoice;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FillSaleDateForCorrectionInvoicesInInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Invoice::all()->each(function ($item) {
                $type = $item->invoiceType->slug;
                if ($type == InvoiceTypeStatus::CORRECTION ||
                    $type == InvoiceTypeStatus::MARGIN_CORRECTION ||
                    $type == InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION ||
                    $type == InvoiceTypeStatus::ADVANCE_CORRECTION
                ) {
                    $item->update(['sale_date' => $item->issue_date]);
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
    }
}
