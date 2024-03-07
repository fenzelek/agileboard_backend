<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Migrations\Migration;

class FillSeviceUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->units() as $unit) {
            ServiceUnit::create($unit);
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
    }

    protected function units()
    {
        return [
            [
                'slug' => 'godz',
                'name' => 'godzina',
                'decimal' => false,
            ],[
                'slug' => 'mc',
                'name' => 'miesiąc',
                'decimal' => false,
            ],[
                'slug' => 'kg',
                'name' => 'kilogram',
                'decimal' => true,
            ],[
                'slug' => 'tona',
                'name' => 'tona',
                'decimal' => true,
            ],[
                'slug' => 'litr',
                'name' => 'litr',
                'decimal' => true,
            ],[
                'slug' => 'm',
                'name' => 'metr',
                'decimal' => true,
            ],[
                'slug' => 'km',
                'name' => 'kilometr',
                'decimal' => true,
            ],[
                'slug' => 'mb',
                'name' => 'metr bieżący',
                'decimal' => true,
            ],[
                'slug' => 'mkw',
                'name' => 'metr kwadratowy',
                'decimal' => true,
            ],[
                'slug' => 'szt',
                'name' => 'sztuka',
                'decimal' => false,
            ],[
                'slug' => 'opak',
                'name' => 'opakowanie',
                'decimal' => false,
            ],[
                'slug' => 'pal',
                'name' => 'paleta',
                'decimal' => false,
            ],[
                'slug' => 'kar',
                'name' => 'karton',
                'decimal' => false,
            ],[
                'slug' => 'usl',
                'name' => 'usługa',
                'decimal' => false,
            ],
        ];
    }
}
