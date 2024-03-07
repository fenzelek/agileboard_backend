<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CompaniesAddVatReleaseReason extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('vat_release_reason_id')->nullable()->default(null)->after('vat_payer');
            $table->text('vat_release_reason_note')->nullable()->default(null)->after('vat_release_reason_id');
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
            $table->dropColumn(['vat_release_reason_id', 'vat_release_reason_note']);
        });
    }
}
