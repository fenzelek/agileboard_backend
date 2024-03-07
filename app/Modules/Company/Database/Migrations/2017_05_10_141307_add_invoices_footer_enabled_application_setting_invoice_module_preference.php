<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\ModuleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInvoicesFooterEnabledApplicationSettingInvoiceModulePreference extends Migration
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
            DB::table('application_settings')->truncate();
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
            ModuleType::INVOICES_FOOTER_ENABLED => [
                'description' => 'Enabled invoice footer',
                'default' => true,
            ],
        ];
    }
}
