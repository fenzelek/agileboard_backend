<?php

use App\Models\Db\Integration\IntegrationProvider;
use Illuminate\Database\Migrations\Migration;

class AddInternalManualIntegrationProviders extends Migration
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
                'slug' => IntegrationProvider::MANUAL,
                'name' => 'Manual Record',
                'type' => IntegrationProvider::TYPE_MANUAL_RECORDING,
            ],
        ]);
    }
}
