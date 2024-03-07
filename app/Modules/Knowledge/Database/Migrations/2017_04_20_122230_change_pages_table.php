<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('knowledge_directory_id')->nullable();
            $table->string('name')->after('knowledge_directory_id')->nullable(false)->change();
            $table->longText('content');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('creator_id');
            $table->dropColumn('knowledge_directory_id');
            $table->dropColumn('content');
            $table->string('name')->nullable()->after('id')->change();
        });
    }
}
