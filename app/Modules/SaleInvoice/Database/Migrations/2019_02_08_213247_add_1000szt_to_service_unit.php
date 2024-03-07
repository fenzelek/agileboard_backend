<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;

class Add1000sztToServiceUnit extends Migration
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
     * Reverse the migrations.
     *
     * @return void
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
                'slug' => '1000szt.',
                'name' => '1000 sztuk',
                'decimal' => 3,
            ],
        ];
    }
}
