<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddDepartmentToUserCompanyTable extends Migration
{
    public function up()
    {
        Schema::table('user_company', function (Blueprint $table) {
            $table->string('department')->after('role_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('user_company', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
}
