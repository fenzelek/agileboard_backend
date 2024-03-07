<?php

use App\Models\Db\Role;
use App\Models\Other\RoleType;
use Illuminate\Database\Migrations\Migration;

class AddApiRolesToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $this->getApiRoleNames()->each(function ($name) {
                $role = Role::findByName($name, true);
                if (! $role) {
                    Role::create(['name' => $name]);
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
            $this->getApiRoleNames()->each(function ($name) {
                $role = Role::findByName($name, true);
                if ($role) {
                    $role->delete();
                }
            });
        });
    }

    /**
     * Get API role names that should be added.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getApiRoleNames()
    {
        return collect([
            RoleType::API_USER,
            RoleType::API_COMPANY,
        ]);
    }
}
