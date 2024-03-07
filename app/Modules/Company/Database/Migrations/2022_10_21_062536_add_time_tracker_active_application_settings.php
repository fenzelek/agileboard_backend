<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\ModuleType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTimeTrackerActiveApplicationSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->settings() as $slug => $info) {
            $application_setting = DB::table('modules')->where('slug', $slug)->first();
            if (empty($application_setting)) {
                DB::table('modules')->insert($info + ['slug' => $slug]);
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
    }

    /**
     * All application settings description.
     *
     * @return array
     */
    public function settings()
    {
        return [
            ModuleType::TIME_TRACKER_ACTIVE => [
                    'name' => 'Active Time Tracker module',
                    'description' => 'Active Time Tracker module',
                    'visible' => true,
                    'available' => true
                ],
        ];
    }
}
