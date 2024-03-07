<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Db\UserCompany;
use App\Models\Other\UserCompanyStatus;
use App\Models\Db\ProjectUser;

class DeleteUserFromProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = UserCompany::where('status', UserCompanyStatus::DELETED)->get();
        foreach ($users as $user) {
            ProjectUser::where('user_id', $user->user_id)
                ->whereHas('project', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                })
                ->delete();
        }
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
