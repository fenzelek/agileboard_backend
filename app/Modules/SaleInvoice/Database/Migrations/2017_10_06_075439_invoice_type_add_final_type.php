<?php

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Database\Migrations\Migration;

class InvoiceTypeAddFinalType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::finalInvoiceTypes() as $slug => $description) {
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
        InvoiceType::whereIn('slug', array_keys($this->finalInvoiceTypes()))->delete();
    }

    public static function finalInvoiceTypes()
    {
        return [
            InvoiceTypeStatus::FINAL_ADVANCE => [
                'description' => 'Faktura Zaliczkowa Końcowa',
                'parent_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            ],
        ];
    }
}
