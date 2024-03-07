<?php

use App\Models\Other\UserCompanyStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_company', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('role_id')->index();
            $table->unsignedTinyInteger('status')->default(UserCompanyStatus::APPROVED);
            $table->string('title', 255)->default('');
            $table->text('skills')->default('');
            $table->text('description')->default('');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_company');
    }
}
