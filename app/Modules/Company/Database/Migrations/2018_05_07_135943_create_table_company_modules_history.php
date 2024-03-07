<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCompanyModulesHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_modules_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('module_id');
            $table->unsignedInteger('module_mod_id');
            $table->unsignedInteger('package_id')->nullable();
            $table->integer('transaction_id')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->unsignedInteger('status')->nullable();
            $table->integer('price')->default(0);
            $table->char('currency', 3);
            $table->integer('vat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_modules_history');
    }
}
