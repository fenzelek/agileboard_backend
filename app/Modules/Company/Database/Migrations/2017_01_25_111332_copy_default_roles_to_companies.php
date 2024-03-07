<?php

use App\Models\Db\Role;
use App\Models\Db\Company;
use Illuminate\Database\Migrations\Migration;

class CopyDefaultRolesToCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Company::all()->each(function ($company) {
                Role::where('default', 1)->each(function ($role) use ($company) {
                    if (! $company->roles->contains($role->id)) {
                        $company->roles()->attach($role->id);
                    }
                });
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
