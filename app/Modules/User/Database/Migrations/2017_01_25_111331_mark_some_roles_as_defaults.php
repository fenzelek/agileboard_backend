<?php

use App\Models\Db\Role;
use App\Models\Other\RoleType;
use Illuminate\Database\Migrations\Migration;

class MarkSomeRolesAsDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            DB::table('roles')->update(['default' => 0]);
            $this->getRolesToBeDefault()->each(function ($role) {
                $role->default = 1;
                $role->save();
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
            $this->getRolesToBeDefault()->each(function ($role) {
                $role->default = 0;
                $role->save();
            });
        });
    }

    /**
     * Get roles that should be default.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRolesToBeDefault()
    {
        return Role::whereIn('name', [
            RoleType::OWNER,
            RoleType::ADMIN,
            RoleType::EMPLOYEE,
        ])->get();
    }
}
