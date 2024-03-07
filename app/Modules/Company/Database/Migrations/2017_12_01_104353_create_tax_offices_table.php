<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_offices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('zip_code', 7);
            $table->string('city');
            $table->string('street');
            $table->string('number', 63);
            $table->string('code', 10);
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
        Schema::dropIfExists('tax_offices');
    }
}
