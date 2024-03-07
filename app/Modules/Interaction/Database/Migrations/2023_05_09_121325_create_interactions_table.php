<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->morphs('source');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('company_id');
            $table->string('event_type');
            $table->string('action_type');
            $table->timestamps();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
            ;

            $table
                ->foreign('project_id')
                ->references('id')
                ->on('projects')
            ;

            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies')
            ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interactions');
    }
}
