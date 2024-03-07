<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Db\InvoiceType;

class InvoiceTypesUpdateDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::description() as $type => $description) {
            InvoiceType::where('slug', $type)->update([
                'description' => $description,
            ]);
        }
    }

    public static function description()
    {
        return [
            InvoiceTypeStatus::VAT => 'Faktura VAT',
            InvoiceTypeStatus::CORRECTION => 'Faktura VAT Korekta',
            InvoiceTypeStatus::PROFORMA => 'Faktura Pro Forma',
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
