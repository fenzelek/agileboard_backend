<?php

use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMultipleUsersSettingToApplicationSettings extends Migration
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
            ModuleType::GENERAL_MULTIPLE_USERS
        )->first();

        if (empty($application_setting)) {
            DB::table('application_settings')->insert([
                'slug' => ModuleType::GENERAL_MULTIPLE_USERS,
                'description' => 'Activate multiple users.',
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
                ModuleType::GENERAL_MULTIPLE_USERS
            )->first();
            if ($application_setting) {
                $application_setting->delete();
            }
        }
    }
}
