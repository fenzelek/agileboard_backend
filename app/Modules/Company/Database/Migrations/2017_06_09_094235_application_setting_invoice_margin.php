<?php

use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplicationSettingInvoiceMargin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->settings() as $slug => $info) {
            $application_setting = DB::table('application_settings')->where('slug', $slug)->first();
            if (empty($application_setting)) {
                DB::table('application_settings')->insert($info + ['slug' => $slug]);
            }
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
                ModuleType::INVOICES_MARGIN_ENABLED
            )->first();
            if ($application_setting) {
                DB::table('application_settings')->where(
                    'slug',
                    ModuleType::INVOICES_MARGIN_ENABLED
                )->delete();
            }
        }
    }

    /**
     * All application settings description.
     *
     * @return array
     */
    public function settings()
    {
        return [
            ModuleType::INVOICES_MARGIN_ENABLED => [
                'description' => 'Invoice Module - Active issuing invoice margin',
                'default' => false,
            ],
        ];
    }
}
