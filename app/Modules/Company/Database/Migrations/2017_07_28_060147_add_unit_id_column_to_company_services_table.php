<?php

use App\Models\Db\ServiceUnit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnitIdColumnToCompanyServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $unit = ServiceUnit::where('slug', 'szt')->first();
            $table->integer('service_unit_id')->default($unit->id)->after('vat_rate_id');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_services', function (Blueprint $table) {
            $table->dropColumn('service_unit_id');
        });
    }
}
