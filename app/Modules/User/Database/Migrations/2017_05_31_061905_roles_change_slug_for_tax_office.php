<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\Role;
use App\Models\Other\RoleType;

class RolesChangeSlugForTaxOffice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $role = Role::findByName('tax.office', true);
        if ($role) {
            $role->update([
                'name' => RoleType::TAX_OFFICE,
                'default' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::findByName(RoleType::TAX_OFFICE, true);
        if ($role) {
            $role->update([
                'name' => 'tax.office',
            ]);
        }
    }
}
