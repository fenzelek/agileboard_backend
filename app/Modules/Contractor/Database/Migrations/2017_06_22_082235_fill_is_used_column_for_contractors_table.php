<?php

use App\Models\Db\Contractor;
use Illuminate\Database\Migrations\Migration;

class FillIsUsedColumnForContractorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Contractor::all()->each(function ($contractor) {
                if ($contractor->invoices()->first()) {
                    $contractor->is_used = true;
                    $contractor->save();
                }
            });
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            Contractor::all()->each(function ($contractor) {
                $contractor->is_used = false;
                $contractor->save();
            });
        });
    }
}
