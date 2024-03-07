<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\ModuleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInitialApplicationSettingInvoiceModulePreference extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->settings() as $slug => $default) {
            $application_setting = DB::table('application_settings')->where('slug', $slug)->first();
            if ($application_setting) {
                DB::table('application_settings')->where('slug', $slug)->update(['default' => $default]);
            }
        }
    }

    /**
     * Reverse the migrations.
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
            ModuleType::PROJECTS_ACTIVE => false,
            ModuleType::GENERAL_INVITE_ENABLED => true,
            ModuleType::GENERAL_WELCOME_URL => 'app.invoices',
            ModuleType::GENERAL_COMPANIES_VISIBLE => true,
            ModuleType::INVOICES_ACTIVE => true,
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => false,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => false,
            ModuleType::INVOICES_PROFORMA_ENABLED => false,
            ModuleType::INVOICES_UE_ENABLED => false,
        ];
    }
}
