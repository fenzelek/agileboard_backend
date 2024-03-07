<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;

class AllowMetrSzescinnyToHaveDecimalValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $service_unit = ServiceUnit::findBySlug('m sześc.');
        if ($service_unit) {
            $service_unit->decimal = true;
            $service_unit->save();
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        $service_unit = ServiceUnit::findBySlug('m sześc.');
        if ($service_unit) {
            $service_unit->decimal = false;
            $service_unit->save();
        }
    }
}
