<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;

class UpdateServiceUnitsSlugs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->units() as $unit) {
                $service = ServiceUnit::where('slug', ($unit['initial_slug']))->firstOrFail();
                $service->slug = $unit['after_slug'];
                if (isset($unit['after_name'])) {
                    $service->name = $unit['after_name'];
                }
                $service->save();
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
                $service = ServiceUnit::where('slug', ($unit['after_slug']))->firstOrFail();
                $service->slug = $unit['initial_slug'];
                if (isset($unit['initial_name'])) {
                    $service->name = $unit['initial_name'];
                }
                $service->save();
            }
        });
    }

    /**
     * Get units that should be modified.
     *
     * @return array
     */
    protected function units()
    {
        return [
            [
                'initial_slug' => 'godz',
                'after_slug' => 'godz.',
            ],
            [
                'initial_slug' => 'mc',
                'after_slug' => 'mies.',
            ],
            [
                'initial_slug' => 'tona',
                'after_slug' => 't',
            ],
            [
                'initial_slug' => 'litr',
                'after_slug' => 'l',
            ],
            [
                'initial_slug' => 'mb',
                'after_slug' => 'm.b.',
            ],
            [
                'initial_slug' => 'mkw',
                'after_slug' => 'mkw.',
            ],
            [
                'initial_slug' => 'szt',
                'after_slug' => 'szt.',
            ],
            [
                'initial_slug' => 'opak',
                'after_slug' => 'opak.',
            ],
            [
                'initial_slug' => 'pal',
                'after_slug' => 'pjł.',
                'initial_name' => 'paleta',
                'after_name' => 'paletowa jednostka ładunkowa',
            ],
            [
                'initial_slug' => 'kar',
                'after_slug' => 'kart.',
            ],
            [
                'initial_slug' => 'usl',
                'after_slug' => 'usł.',
            ],
        ];
    }
}
