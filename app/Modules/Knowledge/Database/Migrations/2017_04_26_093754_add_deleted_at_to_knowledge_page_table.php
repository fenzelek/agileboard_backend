<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedAtToKnowledgePageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('knowledge_pages', function (Blueprint $table) {
            $table->softDeletes();
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
            $table->dropSoftDeletes();
        });
    }
}
