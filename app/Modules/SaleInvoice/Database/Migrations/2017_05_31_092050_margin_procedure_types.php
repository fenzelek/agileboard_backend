<?php

use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Other\InvoiceMarginProcedureType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MarginProcedureTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->margin_procedures() as $slug => $description) {
                InvoiceMarginProcedure::create([
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
    public function margin_procedures()
    {
        return [
            InvoiceMarginProcedureType::USED_PRODUCT => 'procedura marży dla biur podróży',
            InvoiceMarginProcedureType::TOUR_OPERATOR => 'procedura marży – towary używane',
            InvoiceMarginProcedureType::ART => 'procedura marży – dzieła sztuki',
            InvoiceMarginProcedureType::ANTIQUE => 'procedura marży – przedmioty kolekcjonerskie i antyki',
        ];
    }
}
