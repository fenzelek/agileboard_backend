<?php

use App\Models\Db\InvoiceType;
use Illuminate\Database\Migrations\Migration;
use App\Models\Other\InvoiceTypeStatus;

class InvoiceTypesAddNewTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::marginInvoiceTypes() as $slug => $description) {
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
        InvoiceType::whereIn('slug', array_keys($this->marginInvoiceTypes()))->delete();
    }

    public static function marginInvoiceTypes()
    {
        return [
            InvoiceTypeStatus::MARGIN => [
                    'description' => 'Faktura VAT Marża',
                    'parent_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
                ],
            InvoiceTypeStatus::MARGIN_CORRECTION => [
                    'description' => 'Faktura VAT Marża Korekta',
                    'parent_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
                ],
        ];
    }
}
