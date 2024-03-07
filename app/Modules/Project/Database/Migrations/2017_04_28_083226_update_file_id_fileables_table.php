<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFileIdFileablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fileables', function (Blueprint $table) {
            $table->unsignedInteger('file_id')->change();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fileables', function (Blueprint $table) {
            $table->integer('file_id')->change();
        });
    }
}
