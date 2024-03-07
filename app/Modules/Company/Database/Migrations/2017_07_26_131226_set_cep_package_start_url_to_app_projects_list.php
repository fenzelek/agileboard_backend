<?php

use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetCepPackageStartUrlToAppProjectsList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $cep = Package::findBySlug(Package::CEP_FREE);
            $setting = DB::table('application_settings')->where('slug', ModuleType::GENERAL_WELCOME_URL)->first();
            $cep->applicationSettings()->detach($setting->id);
            $cep->applicationSettings()->attach($setting->id, ['value' => 'app.projects-list']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('application_settings')) {
            DB::transaction(function () {
                $cep = Package::findBySlug(Package::CEP_FREE);
                $setting = DB::table('application_settings')->where('slug', ModuleType::GENERAL_WELCOME_URL)->first();
                $cep->applicationSettings()->detach($setting->id);
                $cep->applicationSettings()->attach($setting->id, ['value' => 'app.calendar']);
            });
        }
    }
}
