<?php

use App\Models\Db\Integration\IntegrationProvider;
use Illuminate\Database\Migrations\Migration;

class AddInitialIntegrationProviders extends Migration
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
                'slug' => IntegrationProvider::HUBSTAFF,
                'name' => 'Hubstaff',
                'type' => IntegrationProvider::TYPE_TIME_TRACKING,
            ],
            [
                'slug' => IntegrationProvider::UPWORK,
                'name' => 'Upwork',
                'type' => IntegrationProvider::TYPE_TIME_TRACKING,
            ],
        ]);
    }
}
