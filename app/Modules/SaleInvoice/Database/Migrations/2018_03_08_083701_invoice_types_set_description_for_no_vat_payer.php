<?php

use App\Models\Db\InvoiceType;
use App\Models\Other\SaleInvoice\Payers\Vat;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvoiceTypesSetDescriptionForNoVatPayer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            InvoiceType::all()->each(function ($invoice) {
                if (str_contains($invoice->description, Vat::POST_FIX)) {
                    $filtered_segment = array_filter(explode(' ', $invoice->description), function ($segment) {
                        return trim($segment) != Vat::POST_FIX;
                    });

                    $no_vat_description = implode(' ', $filtered_segment);
                }
                $invoice->no_vat_description = $no_vat_description ?? $invoice->description;
                $invoice->save();
            });
        });
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
