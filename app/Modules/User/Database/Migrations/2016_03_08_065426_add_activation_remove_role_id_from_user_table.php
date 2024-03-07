<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActivationRemoveRoleIdFromUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activated')->after('remember_token')
                ->default(false);
            $table->string('activate_hash')->after('activated');
            $table->dropColumn('role_id');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activated');
            $table->dropColumn('activate_hash');
            $table->unsignedInteger('role_id')->index()->after('last_name');
        });
    }
}
