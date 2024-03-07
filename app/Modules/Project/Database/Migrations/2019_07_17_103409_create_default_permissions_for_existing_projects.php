<?php

use App\Models\Db\Project;
use App\Models\Db\ProjectPermission;
use Illuminate\Database\Migrations\Migration;

class CreateDefaultPermissionsForExistingProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $projects = Project::doesntHave('permission')->get();

        $default_permissions = ProjectPermission::DEFAULT_PERMISSIONS;
        $default_permissions['user_ticket_show'] = $default_permissions['developer_ticket_show'];
        unset($default_permissions['developer_ticket_show']);

        foreach ($projects as $project) {
            $project->permission()->create($default_permissions);
        }
    }
}
