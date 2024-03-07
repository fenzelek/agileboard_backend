<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_services', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Company
            $table->unsignedInteger('company_id')->index();

            // Data
            $table->string('name', 255);
            $table->string('vat_rate', 63)->comment('The same as constants used in app');
            $table->integer('vat_rate_id');

            // Ownerships
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('editor_id');

            // Times
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
        Schema::drop('company_services');
    }
}
