<?php

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Database\Migrations\Migration;

class AddInitialCompanyAndAssignExistingUsersProjectsAndAvailabilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $defaultCompanyName = trim(Config::get('app.default_company_name'));
        if (! $defaultCompanyName) {
            return;
        }

        $company = Company::where('name', $defaultCompanyName)->first();
        if ($company) {
            return;
        }

        DB::transaction(function () use ($defaultCompanyName) {
            // create company
            $company = new Company();
            $company->name = $defaultCompanyName;
            $company->save();

            // assign users
            User::all()->each(function ($user) use ($company) {
                $user->companies()->attach($company->id, [
                    'role_id' => $user->role_id,
                    'status' => UserCompanyStatus::APPROVED,
                ]);
            });

            // assign projects
            $company->projects()->saveMany(Project::all());

            //assign availabilities
            $company->availabilities()->saveMany(UserAvailability::all());
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        $defaultCompanyName = trim(Config::get('app.default_company_name'));
        if (! $defaultCompanyName) {
            return;
        }

        $company = Company::where('name', $defaultCompanyName)->first();
        if (! $company) {
            return;
        }

        DB::transaction(function () use ($company) {
            User::all()->each(function ($user) use ($company) {
                // save role for user
                $company = $user->companies()->withPivot('role_id')->find($company->id);
                $user->role_id = $company->pivot->role_id;
                $user->save();

                // detach users, projects and availabilities from company
                $user->companies()->detach($company->id);
                $company->projects()->each(function ($project) {
                    $project->company()->dissociate();
                });
                $company->availabilities()->each(function ($availability) {
                    $availability->company()->dissociate();
                });
            });

            $company->delete();
        });
    }
}
