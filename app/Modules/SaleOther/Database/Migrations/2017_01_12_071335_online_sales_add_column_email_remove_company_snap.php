<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OnlineSalesAddColumnEmailRemoveCompanySnap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('online_sales', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'company_snap_id']);
            $table->string('email', '50')->nullable()->default(null)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('online_sales', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('company_snap_id');
            $table->dropColumn('email');
        });
    }
}
