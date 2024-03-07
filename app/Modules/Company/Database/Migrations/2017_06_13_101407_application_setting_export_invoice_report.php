<?php

use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicationSettingExportInvoiceReport extends Migration
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
            ModuleType::INVOICES_REGISTER_EXPORT_NAME
        )->first();
        if (empty($application_setting)) {
            DB::table('application_settings')->insert([
                'slug' => ModuleType::INVOICES_REGISTER_EXPORT_NAME,
                'description' => 'Invoice Module - Active export registry to CSV',
            ]);
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
                ModuleType::INVOICES_REGISTER_EXPORT_NAME
            )->first();
            if ($application_setting) {
                DB::table('application_settings')->where(
                    'slug',
                    ModuleType::INVOICES_REGISTER_EXPORT_NAME
                )->delete();
            }
        }
    }
}
