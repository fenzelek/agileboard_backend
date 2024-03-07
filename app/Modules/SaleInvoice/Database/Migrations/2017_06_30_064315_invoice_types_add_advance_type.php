<?php

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Database\Migrations\Migration;

class InvoiceTypesAddAdvanceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::advanceInvoiceTypes() as $slug => $description) {
            InvoiceType::where('slug', $slug)->delete();
            InvoiceType::create(['slug' => $slug] + $description);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        InvoiceType::whereIn('slug', array_keys($this->advanceInvoiceTypes()))->delete();
    }

    public static function advanceInvoiceTypes()
    {
        return [
            InvoiceTypeStatus::ADVANCE => [
                'description' => 'Faktura Zaliczkowa',
            ],
            InvoiceTypeStatus::ADVANCE_CORRECTION => [
                'description' => 'Faktura Zaliczkowa Korekta',
            ],
        ];
    }
}
