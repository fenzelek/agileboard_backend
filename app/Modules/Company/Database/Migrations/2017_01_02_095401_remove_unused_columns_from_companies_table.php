<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUnusedColumnsFromCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('company_snap_id');
            $table->dropColumn('default_drawer_id');
            $table->dropColumn('default_invoice_numbering_id');
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
            $table->unsignedInteger('company_snap_id')->index()->after('name');
            $table->unsignedInteger('default_drawer_id')->nullable()->after('company_snap_id');
            $table->unsignedInteger('default_invoice_numbering_id')->nullable()
                ->after('default_drawer_id');
        });
    }
}
