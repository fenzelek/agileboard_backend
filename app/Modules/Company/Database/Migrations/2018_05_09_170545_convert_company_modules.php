<?php

use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Company;
use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ConvertCompanyModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $companies = Company::get();
            $packages = Package::pluck('slug', 'id')->toArray();
            $package_start_id = Package::where('slug', 'start')->first()->id;

            foreach ($companies as $company) {

                //def package
                $package_id = $company->package_id;

                if ($packages[$company->package_id] == 'premium') {
                    $package_id = $package_start_id;
                }

                $mods = ModPrice::where('package_id', $package_id)->get();
                $start_values = [];

                $transaction = Transaction::create();

                foreach ($mods as $mod) {
                    $start_values[$mod->moduleMod->module_id] = $mod->moduleMod->value;

                    $history = new CompanyModuleHistory();
                    $history->company_id = $company->id;
                    $history->module_id = $mod->moduleMod->module_id;
                    $history->module_mod_id = $mod->module_mod_id;
                    $history->package_id = $package_id;
                    $history->transaction_id = $transaction->id;
                    $history->new_value = $mod->moduleMod->value;
                    $history->created_at = $company->created_at;
                    $history->save();

                    $module = new CompanyModule();
                    $module->company_id = $company->id;
                    $module->module_id = $mod->moduleMod->module_id;
                    $module->package_id = $package_id;
                    $module->value = $mod->moduleMod->value;
                    $module->save();
                }

                //use test in past
                if ($payment = DB::table('package_payments')->where('company_id', $company->id)->where('package_to', '<', Carbon::now())->first()) {
                    $mods_premium = ModPrice::where('package_id', $payment->package_id)
                        ->whereHas('moduleMod', function ($q) {
                            $q->where('test', 1);
                        })
                        ->get();

                    $premium_values = [];

                    $transaction = Transaction::create();

                    foreach ($mods_premium as $mod) {
                        $history = new CompanyModuleHistory();
                        $history->company_id = $company->id;
                        $history->module_id = $mod->moduleMod->module_id;
                        $history->module_mod_id = $mod->module_mod_id;
                        $history->package_id = $payment->package_id;
                        $history->old_value = $start_values[$mod->moduleMod->module_id];
                        $history->new_value = $mod->moduleMod->value;
                        $history->expiration_date = $payment->package_to;
                        $history->transaction_id = $transaction->id;
                        $history->created_at = $payment->package_from;
                        $history->save();

                        $premium_values[$mod->moduleMod->module_id] = $mod->moduleMod->value;
                    }

                    $transaction = Transaction::create();

                    foreach ($mods as $mod) {
                        if (isset($premium_values[$mod->moduleMod->module_id])) {
                            $history = new CompanyModuleHistory();
                            $history->company_id = $company->id;
                            $history->module_id = $mod->moduleMod->module_id;
                            $history->module_mod_id = $mod->module_mod_id;
                            $history->package_id = $package_id;
                            $history->old_value = $premium_values[$mod->moduleMod->module_id];
                            $history->new_value = $mod->moduleMod->value;
                            $history->transaction_id = $transaction->id;
                            $history->created_at = $payment->package_to;
                            $history->save();
                        }
                    }
                }

                //premium package is now used
                if (Carbon::parse($company->package_until)->isFuture()) {

                    //is only test premium
                    $test = DB::table('package_payments')
                        ->where('company_id', $company->id)
                        ->where('package_to', $company->package_unti)
                        ->first();

                    if ($test) {
                        $mods = ModPrice::where('package_id', $company->package_id)
                            ->whereHas(['moduleMod' => function ($q) {
                                $q->where('test', 1);
                            }])
                            ->get();
                    } else {
                        $mods = ModPrice::where('package_id', $company->package_id)
                            ->where('days', 365)
                            ->get();
                    }

                    foreach ($mods as $mod) {

                        //custom company value
                        $special_value = DB::table('company_application_settings')
                            ->where('company_id', $company->id)
                            ->where('application_setting_id', $mod->moduleMod->module_id)
                            ->first();

                        if ($special_value) {
                            $value = $special_value->value;
                            $module_mod_id = ModuleMod::where('module_id', $mod->moduleMod->module_id)
                                ->where('test', $test ? 1 : 0)
                                ->where('value', $value)
                                ->first()->id;
                        } else {
                            $value = $mod->moduleMod->value;
                            $module_mod_id = $mod->module_mod_id;
                        }

                        $transaction = Transaction::create();
                        $history = new CompanyModuleHistory();
                        $history->company_id = $company->id;
                        $history->module_id = $mod->moduleMod->module_id;
                        $history->module_mod_id = $module_mod_id;
                        $history->package_id = $company->package_id;
                        $history->old_value = $start_values[$mod->moduleMod->module_id];
                        $history->new_value = $value;
                        $history->expiration_date = $company->package_until;
                        $history->transaction_id = $transaction->id;
                        $history->save();

                        CompanyModule::where('company_id', $company->id)
                            ->where('module_id', $mod->moduleMod->module_id)
                            ->update([
                                'package_id' => $company->package_id,
                                'value' => $value,
                                'expiration_date' => $company->package_until,
                            ]);
                    }
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
}
