<?php

use Illuminate\Database\Migrations\Migration;

class ExtractCompanyBankAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Db\Company::all()->each(function ($company) {
            if ((empty($company->bank_name) && empty($company->bank_account_number)) || $company->bankAccounts()->count()) {
                return;
            }
            $company->bankAccounts()->create([
                'bank_name' => $company->bank_name,
                'number' => $company->bank_account_number,
                'default' => true,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \App\Models\Db\BankAccount::whereDefault(true)->get()->each(function ($bank_account) {
            $bank_account->company->update([
               'bank_name' => $bank_account->bank_name,
               'bank_account_number' => $bank_account->number,
           ]);
            $bank_account->delete();
        });
    }
}
