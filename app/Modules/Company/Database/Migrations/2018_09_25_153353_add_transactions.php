<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Module;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Transaction;

class AddTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //change external module
        $module = Module::where('slug', 'invoices.registry.export.name')->first();
        CompanyModule::where('module_id', $module->id)->update(['package_id' => null]);
        CompanyModuleHistory::where('module_id', $module->id)->update(['package_id' => null]);

        //for fix dev server
        $transaction = Transaction::create();
        CompanyModuleHistory::whereNull('transaction_id')->update(['transaction_id' => $transaction->id]);
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
}
