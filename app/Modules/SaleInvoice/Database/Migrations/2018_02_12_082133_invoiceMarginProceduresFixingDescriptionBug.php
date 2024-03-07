<?php

use App\Models\Db\InvoiceMarginProcedure;
use App\Models\Other\InvoiceMarginProcedureType;
use Illuminate\Database\Migrations\Migration;

class InvoiceMarginProceduresFixingDescriptionBug extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->margin_procedures() as $slug => $description) {
                $invoice_margin_procedure = InvoiceMarginProcedure::where('slug', $slug)->first();
                if ($invoice_margin_procedure) {
                    $invoice_margin_procedure->update([
                        'description' => $description,
                        ]);
                }
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
        //
    }

    /**
     * Get all available statuses for margin procedures.
     *
     * @return array
     */
    public function margin_procedures()
    {
        return [
            InvoiceMarginProcedureType::USED_PRODUCT => 'procedura marży – towary używane',
            InvoiceMarginProcedureType::TOUR_OPERATOR => 'procedura marży dla biur podróży',
        ];
    }
}
