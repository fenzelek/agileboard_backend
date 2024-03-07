<?php

use App\Models\Db\Contractor;
use App\Models\Db\Invoice;
use Illuminate\Database\Migrations\Migration;

class FixContractorIdForCompaniesWhenContractorWasUpdated extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            $invalid_invoices = Invoice::with('invoiceContractor', 'contractor')
                ->withTrashed()->get()->filter(function ($invoice) {
                    return $invoice->invoiceContractor && $invoice->contractor &&
                        ($invoice->invoiceContractor->name != $invoice->contractor->name ||
                            $invoice->invoiceContractor->vatin != $invoice->contractor->vatin);
                });

            Log::info("Found {$invalid_invoices->count()} possible invalid invoices");

            $invalid_invoices->each(function ($invoice) {
                $possible_contractors = Contractor::where('company_id', $invoice->company_id)
                    ->where('vatin', $invoice->invoiceContractor->vatin)->get();

                if ($possible_contractors->count() == 0) {
                    Log::info("Invoice #{$invoice->id} - found no contractors with vatin {$invoice->invoiceContractor->vatin}");
                } elseif ($possible_contractors->count() == 1) {
                    $new_contractor_id = $possible_contractors[0]->id;

                    Log::info("Updating invoice #{$invoice->id} contractor from {$invoice->contractor_id} to {$new_contractor_id}");

                    $invoice->contractor_id = $new_contractor_id;
                    $invoice->save();

                    $invoice->invoiceContractor->update(['contractor_id' => $new_contractor_id]);
                } else {
                    Log::info("Invoice #{$invoice->id} not updated - found more than 1 contractor with vatin {$invoice->invoiceContractor->vatin}. Manual action is required");
                }
            });
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
    }
}
