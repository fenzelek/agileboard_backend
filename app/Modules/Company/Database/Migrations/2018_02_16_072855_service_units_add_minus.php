<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ServiceUnitsAddMinus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->units() as $unit) {
                ServiceUnit::create($unit);
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        DB::transaction(function () {
            foreach ($this->units() as $unit) {
                $service = ServiceUnit::findBySlug($unit['slug']);
                if ($service) {
                    $service->delete();
                }
            }
        });
    }

    /**
     * Get units that should be added.
     *
     * @return array
     */
    protected function units()
    {
        return [
            [
                'slug' => '-',
                'name' => 'brak',
                'decimal' => false,
            ],
        ];
    }
}
