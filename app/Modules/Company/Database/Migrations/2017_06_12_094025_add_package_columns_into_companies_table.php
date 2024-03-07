<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPackageColumnsIntoCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('package_id')->nullable()->default(null)->after('name');
            $table->dateTime('package_until')->nullable()->default(null)->after('package_id');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('companies', 'package_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn(['package_id']);
            });
        }
        if (Schema::hasColumn('companies', 'package_until')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn(['package_until']);
            });
        }
    }
}
