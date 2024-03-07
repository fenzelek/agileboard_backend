<?php

use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddApplicationSettingsIntoPackageApplicationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $this->packages()->each(function ($data, $slug) {
                /** @var Package $package */
                $package = Package::where('slug', $slug)->first();
                if (! $package) {
                    return true;
                }

                // array [
                //         application_setting_1 => ['value' => 1],
                //         application_setting_2 => ['value' => 0],
                //       ]
                // we cannot use mapWithKeys - see https://github.com/laravel/framework/issues/15409
                $settings = collect($data)->map(function ($value, $slug) {
                    return [
                        'id' => DB::table('application_settings')->where('slug', $slug)->first()->id,
                        'value' => $value,

                    ];
                })->keyBy('id')->map(function ($value) {
                    return array_except($value, 'id');
                })->all();

                $package->applicationSettings()->sync($settings);
            });
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction(function () {
            $this->packages()->each(function ($data, $slug) {
                /** @var Package $package */
                $package = Package::where('slug', $slug)->first();
                if (! $package) {
                    return true;
                }
            });
        });
    }

    protected function packages()
    {
        return collect([
            Package::START => [
                ModuleType::PROJECTS_ACTIVE => '0',
                ModuleType::GENERAL_INVITE_ENABLED => '1',
                ModuleType::GENERAL_WELCOME_URL => 'app.dashboard',
                ModuleType::GENERAL_COMPANIES_VISIBLE => '1',
                ModuleType::INVOICES_ACTIVE => '1',
                ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => '0',
                ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => '0',
                ModuleType::INVOICES_PROFORMA_ENABLED => '0',
                ModuleType::INVOICES_UE_ENABLED => '0',
                ModuleType::INVOICES_MARGIN_ENABLED => '0',
                ModuleType::INVOICES_FOOTER_ENABLED => '1',
                ModuleType::RECEIPTS_ACTIVE => '0',
            ],
            Package::PREMIUM => [
                ModuleType::PROJECTS_ACTIVE => '0',
                ModuleType::GENERAL_INVITE_ENABLED => '1',
                ModuleType::GENERAL_WELCOME_URL => 'app.dashboard',
                ModuleType::GENERAL_COMPANIES_VISIBLE => '1',
                ModuleType::INVOICES_ACTIVE => '1',
                ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => '1',
                ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => '1',
                ModuleType::INVOICES_PROFORMA_ENABLED => '1',
                ModuleType::INVOICES_UE_ENABLED => '1',
                ModuleType::INVOICES_MARGIN_ENABLED => '1',
                ModuleType::INVOICES_FOOTER_ENABLED => '1',
                ModuleType::RECEIPTS_ACTIVE => '0',
            ],
            Package::CEP_FREE => [
                ModuleType::PROJECTS_ACTIVE => '1',
                ModuleType::GENERAL_INVITE_ENABLED => '1',
                ModuleType::GENERAL_WELCOME_URL => 'app.calendar',
                ModuleType::GENERAL_COMPANIES_VISIBLE => '1',
                ModuleType::INVOICES_ACTIVE => '0',
                ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => '0',
                ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => '0',
                ModuleType::INVOICES_PROFORMA_ENABLED => '0',
                ModuleType::INVOICES_UE_ENABLED => '0',
                ModuleType::INVOICES_MARGIN_ENABLED => '0',
                ModuleType::INVOICES_FOOTER_ENABLED => '0',
                ModuleType::RECEIPTS_ACTIVE => '0',
            ],

            Package::ICONTROL => [
                ModuleType::PROJECTS_ACTIVE => '0',
                ModuleType::GENERAL_INVITE_ENABLED => '0',
                ModuleType::GENERAL_WELCOME_URL => 'app.invoices-list',
                ModuleType::GENERAL_COMPANIES_VISIBLE => '0',
                ModuleType::INVOICES_ACTIVE => '1',
                ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED => '0',
                ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE => '0',
                ModuleType::INVOICES_PROFORMA_ENABLED => '0',
                ModuleType::INVOICES_UE_ENABLED => '0',
                ModuleType::INVOICES_MARGIN_ENABLED => '0',
                ModuleType::INVOICES_FOOTER_ENABLED => '0',
                ModuleType::RECEIPTS_ACTIVE => '1',
            ],
        ]);
    }
}
