<?php

use Illuminate\Database\Migrations\Migration;

class FixRbgServiceUnit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $service = \App\Models\Db\ServiceUnit::findBySlug('rgb');
        $service->update(['slug' => 'RBG']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $service = \App\Models\Db\ServiceUnit::findBySlug('RBG');
        $service->update(['slug' => 'rgb']);
    }
}
