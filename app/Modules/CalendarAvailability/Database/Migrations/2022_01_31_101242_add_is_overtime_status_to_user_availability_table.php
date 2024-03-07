<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsOvertimeStatusToUserAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_availability', function (Blueprint $table) {
            $table->boolean('overtime')->after('available')->default(false);
            $table->enum('status', ['ADDED', 'CONFIRMED'])->after('overtime')->default('ADDED');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_availability', function (Blueprint $table) {
            $table->dropColumn('overtime');
            $table->dropColumn('status');
        });
    }
}
