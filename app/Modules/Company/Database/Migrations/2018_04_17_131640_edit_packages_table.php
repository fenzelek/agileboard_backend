<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('portal_name')->nullable()->after('default');
            $table->dropColumn('public');
        });

        $portal_name = [
            'start' => 'fv',
            'premium' => 'fv',
            'cep' => 'ab',
            'icontrol' => 'icontrol',
        ];

        foreach (\App\Models\Db\Package::all() as $package) {
            if (array_has($portal_name, $package->slug)) {
                $package->portal_name = $portal_name[$package->slug];
                $package->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('portal_name');
            $table->boolean('public')->default(true);
        });
    }
}
