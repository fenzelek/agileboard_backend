<?php

use App\Models\Db\Integration\IntegrationProvider;
use Illuminate\Database\Migrations\Migration;

class AddInternalTimeTrackerIntegrationProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $this->getRecords()->each(function ($record) {
                IntegrationProvider::create($record);
            });
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            $this->getRecords()->each(function ($record) {
                IntegrationProvider::where($record)->delete();
            });
        });
    }

    protected function getRecords()
    {
        return collect([
            [
                'slug' => IntegrationProvider::TIME_TRACKER,
                'name' => 'Time Tracker',
                'type' => IntegrationProvider::TYPE_INTERNAL_TIME_TRACKING,
            ],
        ]);
    }
}
