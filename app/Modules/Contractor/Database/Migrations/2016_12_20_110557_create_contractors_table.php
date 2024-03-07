<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contractors', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Snap
            /*
             * Pointer to current snapshot.
             */
            $table->unsignedInteger('contractors_snap_id')->index();

            // Data
            $table->string('vatin', 15);

            // Defaults
            $table->unsignedInteger('default_payment_term_days')->nullable();
            $table->unsignedInteger('default_payment_method_id')->nullable();

            // Ownerships
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('editor_id');
            $table->unsignedInteger('remover_id');

            // Times
            $table->timestamps();
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
        Schema::drop('contractors');
    }
}
