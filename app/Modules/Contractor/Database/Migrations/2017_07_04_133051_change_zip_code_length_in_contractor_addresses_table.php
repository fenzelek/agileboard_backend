<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeZipCodeLengthInContractorAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contractor_addresses', function (Blueprint $table) {
            $table->string('zip_code', 255)->change();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contractor_addresses', function (Blueprint $table) {
            $table->string('zip_code', 7)->change();
        });
    }
}
