<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgePageCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_page_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('knowledge_page_id');
            $table->string('type', 20);
            $table->string('ref')->nullable();
            $table->unsignedInteger('user_id');
            $table->text('text')->nullable();
            $table->timestamps();
        });

        Schema::table('knowledge_page_comments', function (Blueprint $table) {
            $table->index('knowledge_page_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('knowledge_page_comments');
    }
}
