<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveColumnsFromSnapToContractorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->string('name', 255)->after('id');
            $table->string('email', 255)->after('vatin');
            $table->string('phone', 15)->after('email');
            $table->string('bank_name', 63)->after('phone');
            $table->string('bank_account_number', 63)->after('bank_name');

            // Addresses
            $table->string('main_address_street', 255)->after('bank_account_number');
            $table->string('main_address_number', 31)->after('main_address_street');
            $table->string('main_address_zip_code', 7)->after('main_address_number');
            $table->string('main_address_city', 63)->after('main_address_zip_code');
            $table->string('main_address_country', 63)->after('main_address_city');

            $table->string('contact_address_street', 255)->after('main_address_country');
            $table->string('contact_address_number', 31)->after('contact_address_street');
            $table->string('contact_address_zip_code', 7)->after('contact_address_number');
            $table->string('contact_address_city', 63)->after('contact_address_zip_code');
            $table->string('contact_address_country', 63)->after('contact_address_city');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('email');
            $table->dropColumn('phone');
            $table->dropColumn('bank_name');
            $table->dropColumn('bank_account_number');

            // Addresses
            $table->dropColumn('main_address_street');
            $table->dropColumn('main_address_number');
            $table->dropColumn('main_address_zip_code');
            $table->dropColumn('main_address_city');
            $table->dropColumn('main_address_country');

            $table->dropColumn('contact_address_street');
            $table->dropColumn('contact_address_number');
            $table->dropColumn('contact_address_zip_code');
            $table->dropColumn('contact_address_city');
            $table->dropColumn('contact_address_country');
        });
    }
}
