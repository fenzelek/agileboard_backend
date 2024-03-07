<?php

use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDefaultNameOfAppInvoicesWelcomeUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $application_setting = DB::table('application_settings')->where(
            'slug',
            ModuleType::GENERAL_WELCOME_URL
        )->first();

        if ($application_setting && $application_setting->default == 'app.invoices') {
            DB::table('application_settings')
                ->where('slug', ModuleType::GENERAL_WELCOME_URL)
                ->update(['default' => 'app.invoices-list']);
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('application_settings')) {
            $application_setting = DB::table('application_settings')->where(
                'slug',
                ModuleType::GENERAL_WELCOME_URL
            )->first();

            if ($application_setting && $application_setting->default == 'app.invoices-list') {
                DB::table('application_settings')
                    ->where('slug', ModuleType::GENERAL_WELCOME_URL)
                    ->update(['default' => 'app.invoices']);
            }
        }
    }
}
