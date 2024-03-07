<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceRegistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_registries', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Format
            $table->unsignedInteger('invoice_format_id');

            // Data
            $table->string('name');
            $table->string('prefix', 15);

            // Ownerships
            $table->unsignedInteger('company_id');
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
        Schema::drop('invoice_registries');
    }
}
