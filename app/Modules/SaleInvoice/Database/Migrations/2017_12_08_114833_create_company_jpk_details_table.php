<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyJpkDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_jpk_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('regon', 20)->nullable()->default(null);
            $table->string('state');
            $table->string('county');
            $table->string('community');
            $table->string('street');
            $table->string('building_number')->nullable()->default(null);
            $table->string('flat_number')->nullable()->default(null);
            $table->string('city');
            $table->string('zip_code');
            $table->string('postal');
            $table->unsignedInteger('tax_office_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('company_jpk_details');
    }
}
