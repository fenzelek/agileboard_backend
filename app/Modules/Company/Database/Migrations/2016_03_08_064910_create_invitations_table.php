<?php

use App\Models\Other\InvitationStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->string('unique_hash')->primary();
            $table->string('email');
            $table->unsignedInteger('inviting_user_id');
            $table->unsignedInteger('company_id');
            $table->timestamp('expiration_time')->nullable();
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->unsignedInteger('role_id');
            $table->unsignedTinyInteger('status')->default(InvitationStatus::PENDING);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invitations');
    }
}
