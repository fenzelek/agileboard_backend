<?php

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Database\Migrations\Migration;

class InvoiceTypesAddReverseChargeTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::reverseChargeInvoiceTypes() as $slug => $description) {
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
        InvoiceType::whereIn('slug', array_keys($this->reverseChargeInvoiceTypes()))->delete();
    }

    public static function reverseChargeInvoiceTypes()
    {
        return [
            InvoiceTypeStatus::REVERSE_CHARGE => [
                'description' => 'Faktura VAT Odwrotne Obciążenie',
                'parent_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            ],
            InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION => [
                'description' => 'Faktura VAT Odwrotne Obciążenie Korekta',
                'parent_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            ],
        ];
    }
}
