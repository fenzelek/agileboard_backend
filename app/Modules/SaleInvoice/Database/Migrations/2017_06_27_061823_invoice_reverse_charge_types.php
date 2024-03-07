<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Other\InvoiceReverseChargeType;

class InvoiceReverseChargeTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->reverseChargeTypes() as $slug => $description) {
                InvoiceReverseCharge::create([
                    'slug' => $slug,
                    'description' => $description,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('invoice_margin_procedures')->truncate();
    }

    /**
     * Get all available statuses for margin procedures.
     *
     * @return array
     */
    public function reverseChargeTypes()
    {
        return [
            InvoiceReverseChargeType::IN => 'Krajowy',
            InvoiceReverseChargeType::OUT => 'Poza terytorium kraju',
            InvoiceReverseChargeType::IN_UE => 'Wewnątrzunijny',
            InvoiceReverseChargeType::IN_EU_TRIPLE => 'Wewnątrzunijny trójstronny',
            InvoiceReverseChargeType::OUT_EU => 'Pozaunijny',
            InvoiceReverseChargeType::OUT_EU_TAX_BACK => 'Pozaunijny (zwroty vat)',
            InvoiceReverseChargeType::CUSTOMER_TAX => 'Podatnikiem jest nabywca',
            InvoiceReverseChargeType::OUT_NP => 'Poza terytorium kraju (stawka NP)',
            InvoiceReverseChargeType::IN_EU_CUSTOMER_TAX => 'Wewnątrzunijny - Podatnikiem jest nabywca',
            InvoiceReverseChargeType::OUT_EU_CUSTOMER_TAX => 'Pozaunijny - Podatnikiem jest nabywca',
        ];
    }
}
