<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Package;

class PackegesSetDefaultPachageStart extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $package = Package::findBySlug(Package::START);
        if ($package) {
            $package->update(['default' => true]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $package = Package::findBySlug(Package::START);
        if ($package) {
            $package->update(['default' => false]);
        }
    }
}
