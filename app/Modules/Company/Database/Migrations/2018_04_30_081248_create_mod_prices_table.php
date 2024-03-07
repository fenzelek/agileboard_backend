<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mod_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('module_mod_id');
            $table->unsignedInteger('package_id')->nullable();
            $table->unsignedInteger('days')->nullable();
            $table->integer('price')->nullable();
            $table->integer('vat')->nullable();
            $table->char('currency', 3);
            $table->timestamps();
        });

        $premium_id = DB::table('packages')->where('slug', 'premium')->first()->id;
        $modules = DB::table('package_application_settings')->get();

        foreach ($modules as $module) {
            if (DB::table('package_modules')->where('module_id', $module->application_setting_id)->where('package_id', $module->package_id)->first()) {
                $mods = DB::table('module_mods')->where('module_id', $module->application_setting_id)
                    ->where('value', $module->value)->get();
                foreach ($mods as $mod) {
                    if ($premium_id == $module->package_id) {
                        $this->createModPrice($mod, 30, $module->package_id);
                        if (! $mod->test) {
                            $this->createModPrice($mod, 365, $module->package_id);
                        }
                    } elseif (! $mod->test) {
                        $this->createModPrice($mod, null, $module->package_id);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mod_prices');
    }

    private function createModPrice($module_mod, $days, $package_id)
    {
        DB::table('mod_prices')->insert([
            'module_mod_id' => $module_mod->id,
            'package_id' => $package_id,
            'days' => $days,
            'price' => 0,
            'vat' => 23,
            'currency' => 'PLN',
        ]);
    }
}
