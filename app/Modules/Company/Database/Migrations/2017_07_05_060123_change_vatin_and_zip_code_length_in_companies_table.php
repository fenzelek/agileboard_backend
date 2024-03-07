<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeVatinAndZipCodeLengthInCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vatin', 255)->change();
            $table->string('main_address_zip_code', 255)->change();
            $table->string('contact_address_zip_code', 255)->change();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vatin', 15)->change();
            $table->string('main_address_zip_code', 7)->change();
            $table->string('contact_address_zip_code', 7)->change();
        });
    }
}
