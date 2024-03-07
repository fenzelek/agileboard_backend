<?php

use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddJpkSettingToApplicationSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $application_setting = DB::table('application_settings')->where(
            'slug',
            ModuleType::INVOICES_JPK_EXPORT
        )->first();

        if (empty($application_setting)) {
            DB::table('application_settings')->insert([
                'slug' => ModuleType::INVOICES_JPK_EXPORT,
                'description' => 'Invoice module - export to JPK',
            ]);
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('application_settings')) {
            $application_setting = DB::table('application_settings')->where(
                'slug',
                ModuleType::INVOICES_JPK_EXPORT
            )->first();
            if ($application_setting) {
                DB::table('application_settings')->where(
                    'slug',
                    ModuleType::INVOICES_JPK_EXPORT
                )->delete();
            }
        }
    }
}
