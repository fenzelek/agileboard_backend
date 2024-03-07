<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterExtendCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {

            // Snap
            /*
             * Pointer to current snapshot.
             */
            $table->unsignedInteger('company_snap_id')->index();

            // Data
            $table->string('vatin', 15);

            // Defaults
            $table->unsignedInteger('default_drawer_id')->nullable();
            $table->unsignedInteger('default_invoice_numbering_id')->nullable();
            $table->unsignedInteger('default_payment_term_days')->nullable();
            $table->unsignedInteger('default_payment_method_id')->nullable();

            // Ownerships
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('editor_id');

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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['company_snap_id',
                'vatin',
                'default_drawer_id',
                'default_invoice_numbering_id',
                'default_payment_term_days',
                'default_payment_method_id',
                'creator_id',
                'editor_id',
            ]);

            // Times
            $table->dropTimestamps();
        });
    }
}
