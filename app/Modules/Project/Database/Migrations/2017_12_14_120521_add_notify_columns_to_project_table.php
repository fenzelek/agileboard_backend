<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotifyColumnsToProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('email_notification_enabled')->default(0)->after('time_tracking_visible_for_clients');
            $table->boolean('slack_notification_enabled')->default(0)->after('email_notification_enabled');
            $table->string('slack_webhook_url')->nullable()->after('slack_notification_enabled');
            $table->string('slack_channel')->nullable()->after('slack_webhook_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('email_notification_enabled');
            $table->dropColumn('slack_notification_enabled');
            $table->dropColumn('slack_webhook_url');
            $table->dropColumn('slack_channel');
        });
    }
}
