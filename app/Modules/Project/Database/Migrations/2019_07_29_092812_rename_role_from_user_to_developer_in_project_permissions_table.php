<?php

use App\Models\Db\ProjectPermission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameRoleFromUserToDeveloperInProjectPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_permissions', function (Blueprint $table) {
            $table->renameColumn('user_ticket_show', 'developer_ticket_show');
        });

        $permissions = ProjectPermission::get();
        foreach ($permissions as $permission) {
            $this->renameRoles('user', 'developer', $permission);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_permissions', function (Blueprint $table) {
            $table->renameColumn('developer_ticket_show', 'user_ticket_show');
        });

        $permissions = ProjectPermission::get();
        foreach ($permissions as $permission) {
            $this->renameRoles('developer', 'user', $permission);
        }
    }

    /**
     * @param $from
     * @param $to
     * @param $permission
     */
    private function renameRoles($from, $to, $permission)
    {
        // tickets
        $key = array_search(
            $from,
            array_column($permission->ticket_create['roles'], 'name')
        );
        $tab = $permission->ticket_create;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_create = $tab;

        $key = array_search(
            $from,
            array_column($permission->ticket_update['roles'], 'name')
        );
        $tab = $permission->ticket_update;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_update = $tab;

        $key = array_search(
            $from,
            array_column($permission->ticket_destroy['roles'], 'name')
        );
        $tab = $permission->ticket_destroy;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_destroy = $tab;

        // ticket comments
        $key = array_search(
            $from,
            array_column($permission->ticket_comment_create['roles'], 'name')
        );
        $tab = $permission->ticket_comment_create;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_comment_create = $tab;

        $key = array_search(
            $from,
            array_column($permission->ticket_comment_update['roles'], 'name')
        );
        $tab = $permission->ticket_comment_update;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_comment_update = $tab;

        $key = array_search(
            $from,
            array_column($permission->ticket_comment_destroy['roles'], 'name')
        );
        $tab = $permission->ticket_comment_destroy;
        $tab['roles'][$key]['name'] = $to;
        $permission->ticket_comment_destroy = $tab;

        $permission->save();
    }
}
