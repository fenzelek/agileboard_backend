<?php

use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToUserAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_availability', function (Blueprint $table) {
            $table->enum('source', UserAvailabilitySourceType::all())->after('status')->default(UserAvailabilitySourceType::INTERNAL);
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
            $table->dropColumn('source');
        });
    }
}
