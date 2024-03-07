<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;

class InvoiceTypesAddProforma extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $type_proforma = InvoiceType::where('slug', '=', InvoiceTypeStatus::PROFORMA)->first();
        if (empty($type_proforma)) {
            $type_proforma = new InvoiceType();
            $type_proforma->slug = InvoiceTypeStatus::PROFORMA;
            $type_proforma->description = 'Faktura proforma';
            $type_proforma->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $type_proforma = InvoiceType::where('slug', '=', InvoiceTypeStatus::PROFORMA)->first();
        if ($type_proforma) {
            $type_proforma->delete();
        }
    }
}
