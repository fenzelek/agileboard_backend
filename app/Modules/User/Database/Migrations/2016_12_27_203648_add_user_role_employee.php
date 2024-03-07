<?php

use App\Models\Db\Role;
use App\Models\Other\RoleType;
use Illuminate\Database\Migrations\Migration;

class AddUserRoleEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $role = Role::findByName(RoleType::EMPLOYEE, true);
            if (! $role) {
                Role::create(['name' => RoleType::EMPLOYEE]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            $role = Role::findByName(RoleType::EMPLOYEE, true);
            if ($role) {
                $role->delete();
            }
        });
    }
}
