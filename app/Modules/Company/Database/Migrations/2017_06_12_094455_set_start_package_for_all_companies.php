<?php

use App\Models\Db\Company;
use App\Models\Db\Package;
use Illuminate\Database\Migrations\Migration;

class SetStartPackageForAllCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $start_package = Package::where('slug', Package::START)->first();

        Company::all()->each(function ($company) use ($start_package) {
            $company->package_id = $start_package->id;
            $company->save();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('companies', 'package_id')) {
            Company::all()->each(function ($company) {
                $company->package_id = null;
                $company->save();
            });
        }
    }
}
