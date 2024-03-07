<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Role;
use App\Models\Other\RoleType;

class RolesAddTaxOfficeRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $role = Role::findByName(RoleType::TAX_OFFICE, true);
            if (! $role) {
                Role::create([
                    'name' => RoleType::TAX_OFFICE,
                    'default' => true,
                ]);
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
            $role = Role::findByName(RoleType::TAX_OFFICE, true);
            if ($role) {
                $role->delete();
            }
        });
    }
}
