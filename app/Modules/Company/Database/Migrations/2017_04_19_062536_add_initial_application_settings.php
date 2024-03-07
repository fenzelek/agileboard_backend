<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Other\ModuleType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddInitialApplicationSettings extends Migration
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
            ModuleType::PROJECTS_ACTIVE => [
                    'description' => 'Active project module',
                    'default' => true,
                ],
            ModuleType::GENERAL_INVITE_ENABLED => [
                    'description' => 'Active adding user by invitation',
                    'default' => true,
                ],
            ModuleType::GENERAL_WELCOME_URL => [
                    'description' => 'Welcome url',
                    'default' => 'app.calendar',
                ],
            ModuleType::GENERAL_COMPANIES_VISIBLE => [
                    'description' => 'Menu with visible companies',
                    'default' => true,
                ],
            ModuleType::INVOICES_ACTIVE => [
                    'description' => 'Active invoice module',
                    'default' => false,
                ],
            ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => [
                    'description' => 'CreateInvoice Module - active adding delivery addresses',
                    'default' => false,
                ],
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => [
                    'description' => 'CreateInvoice Module - customising service name on issuing invoice',
                    'default' => false,
                ],
            ModuleType::INVOICES_PROFORMA_ENABLED => [
                    'description' => 'CreateInvoice Module - Active issuing proforma',
                    'default' => false,
                ],
            ModuleType::INVOICES_UE_ENABLED => [
                    'description' => 'CreateInvoice Module - Active issuing UE invoice',
                    'default' => false,
                ],
        ];
    }
}
