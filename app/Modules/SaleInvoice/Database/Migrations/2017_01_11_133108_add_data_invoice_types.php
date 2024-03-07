<?php

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use Illuminate\Database\Migrations\Migration;

class AddDataInvoiceTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoice_types_data = [
            [
                'slug' => Invoice::TYPE_VAT,
                'description' => 'Faktura VAT',
            ],
            [
                'slug' => Invoice::TYPE_CORRECTION,
                'description' => 'Faktura korygująca',
            ],
        ];

        foreach ($invoice_types_data as $item) {
            $invoice_type = new InvoiceType();
            $invoice_type->slug = $item['slug'];
            $invoice_type->description = $item['description'];
            $invoice_type->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        InvoiceType::truncate();
    }
}
