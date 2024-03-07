<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeFileRoleToPermissionRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::rename('file_role', 'permission_role');
            Schema::table('permission_role', function (Blueprint $table) {
                $table->renameColumn('file_id', 'permissionable_id');
                $table->string('permissionable_type', 255)->after('file_id');
                $table->index(['permissionable_id', 'permissionable_type']);
                $table->dropIndex('file_role_role_id_index');
                $table->dropIndex('file_role_file_id_index');
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
            Schema::table('permission_role', function (Blueprint $table) {
                $table->dropIndex('permission_role_permissionable_id_permissionable_type_index');
                $table->dropColumn('permissionable_type');
                $table->renameColumn('permissionable_id', 'file_id');
                $table->index('file_id');
                $table->index('role_id');
            });
            Schema::rename('permission_role', 'file_role');
        });
    }
}
