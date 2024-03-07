<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModuleModsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_mods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('module_id');
            $table->boolean('test')->default(false);
            $table->string('value')->nullable();
            $table->timestamps();
        });

        $this->premium_packages = \App\Models\Db\Package::whereIn('slug', [
            \App\Models\Db\Package::PREMIUM,
            ])
            ->get()
            ->pluck('id')
            ->toArray();

        $exceptions = [
            'general.welcome_url' => [
                'ab' => 'app.projects-list',
                'fv' => 'app.dashboard',
                'icontrol' => 'app.invoices-list',
            ],
            'invoices.registry.export.name' => [
                'optima',
                'firmen',
                '',
            ],
        ];

        foreach (\App\Models\Db\Module::get() as $module) {
            if (array_has($exceptions, $module->slug)) {
                foreach ($exceptions[$module->slug] as $mod) {
                    $this->createMod($module->id, $mod);
                }
            } else {
                $this->createMod($module->id, 1);
                $this->createMod($module->id, 0);

                if ($this->isPremium($module->id)) {
                    $this->createMod($module->id, 1, true);
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
        Schema::dropIfExists('module_mods');
    }

    private function createMod($module_id, $value, $test = false)
    {
        $module_mod = new \App\Models\Db\ModuleMod();
        $module_mod->test = $test;
        $module_mod->module_id = $module_id;
        $module_mod->value = $value;
        $module_mod->save();
    }

    private function isPremium($module_id)
    {
        $packages = \Illuminate\Support\Facades\DB::table('package_application_settings')
            ->where('application_setting_id', $module_id)
            ->get()
            ->pluck('package_id')
            ->toArray();

        foreach ($packages as $package) {
            if (in_array($package, $this->premium_packages)) {
                return true;
            }
        }

        return false;
    }
}
