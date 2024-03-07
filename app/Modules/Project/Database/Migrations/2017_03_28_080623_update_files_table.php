<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->unsignedInteger('project_id')->default(0)->after('id');
            $table->unsignedInteger('user_id')->default(0)->after('project_id');
            $table->string('storage_name', 255)->unique()->default('')->after('name');
            $table->integer('size')->nullable()->after('storage_name');
            $table->string('extension', 4)->nullable()->after('size');
            $table->string('description', 255)->nullable()->after('extension');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->dropColumn('user_id');
            $table->dropColumn('storage_name');
            $table->dropColumn('size');
            $table->dropColumn('extension');
            $table->dropColumn('description');
        });
    }
}
