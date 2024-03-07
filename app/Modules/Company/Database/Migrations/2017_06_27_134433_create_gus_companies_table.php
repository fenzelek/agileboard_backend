<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGusCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gus_companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('vatin');
            $table->string('regon');
            $table->string('main_address_country');
            $table->string('main_address_zip_code');
            $table->string('main_address_city');
            $table->string('main_address_street');
            $table->string('main_address_number');
            $table->string('phone');
            $table->string('email');
            $table->string('website');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gus_companies');
    }
}
