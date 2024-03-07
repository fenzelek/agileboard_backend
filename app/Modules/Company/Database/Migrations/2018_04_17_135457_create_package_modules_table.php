<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackageModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_modules', function (Blueprint $table) {
            $table->unsignedInteger('package_id');
            $table->unsignedInteger('module_id');
            $table->timestamps();
        });

        $fv_packages = DB::table('packages')->where('portal_name', 'fv')->orWhere('portal_name', 'icontrol')->pluck('id')->toArray();
        $ab_packages = DB::table('packages')->where('portal_name', 'ab')->pluck('id')->toArray();
        $modules = DB::table('modules')->pluck('slug', 'id');

        $fv_except_modules = ['projects.active'];
        $ab_only_modules = ['projects.active', 'general.invite.enabled', 'general.welcome_url',
            'general.companies.visible', 'general.multiple_users', ];

        foreach (DB::table('package_application_settings')->get() as $setting) {
            if (in_array($setting->package_id, $fv_packages)) {
                //if in array
                if (! in_array($modules[$setting->application_setting_id], $fv_except_modules)) {
                    $package_module = new \App\Models\Db\PackageModule();
                    $package_module->package_id = $setting->package_id;
                    $package_module->module_id = $setting->application_setting_id;
                    $package_module->save();
                }
            } elseif (in_array($setting->package_id, $ab_packages)) {
                //if not in array
                if (in_array($modules[$setting->application_setting_id], $ab_only_modules)) {
                    $package_module = new \App\Models\Db\PackageModule();
                    $package_module->package_id = $setting->package_id;
                    $package_module->module_id = $setting->application_setting_id;
                    $package_module->save();
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
        Schema::dropIfExists('package_modules');
    }
}
