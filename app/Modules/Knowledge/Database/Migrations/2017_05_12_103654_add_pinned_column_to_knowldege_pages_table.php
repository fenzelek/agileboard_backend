<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPinnedColumnToKnowldegePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('knowledge_pages', function (Blueprint $table) {
            $table->boolean('pinned')->default(false)->after('content');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('knowledge_pages', function (Blueprint $table) {
            $table->dropColumn('pinned');
        });
    }
}
