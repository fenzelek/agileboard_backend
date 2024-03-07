<?php

use App\Models\Db\User;
use Illuminate\Database\Migrations\Migration;

class MakeAllExistingUsersActive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            User::all()->each(function ($user) {
                $user->activated = true;
                $user->save();
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
            User::all()->each(function ($user) {
                $user->activated = false;
                $user->save();
            });
        });
    }
}
