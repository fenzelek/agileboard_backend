<?php

use Illuminate\Database\Migrations\Migration;

class RenamePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('pages', 'knowledge_pages');
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('knowledge_pages', 'pages');
    }
}
