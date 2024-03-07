<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {

            // Id
            $table->increments('id');

            // Ownerships
            $table->unsignedInteger('drawer_id');
            $table->unsignedInteger('company_id')->index();

            // Numbers
            $table->string('doc_number', 63);
            $table->unsignedInteger('documentable_id')->comment('Reference to invoice, receipt... entry via id field.');
            $table->string('documentable_type', 63);

            // Data
            $table->integer('price_net');
            $table->integer('price_gross');
            $table->integer('payment_left');

            // Status
            $table->string('status', 31);

            // Transaction times
            $table->timestamp('sale_date');
            $table->timestamp('issue_date');

            // Times
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('documents');
    }
}
