<?php

use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PackageApplicationSettingAddReverseChargeSetting extends Migration
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
                    return;
                }
                $setting = DB::table('application_settings')->where('slug', $data['setting'])->first();
                if (! $setting) {
                    return;
                }
                $package->applicationSettings()->attach([$setting->id => ['value' => $data['value']]]);
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
                    return;
                }
                if (Schema::hasTable('application_settings')) {
                    $setting = DB::table('application_settings')->where('slug', $data['setting'])->first();
                    if (! $setting) {
                        return;
                    }
                    $package->applicationSettings()->detach($setting->id);
                }
            });
        });
    }

    protected function packages()
    {
        return collect([
            Package::START => [
                'setting' => ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
                'value' => '0',
            ],
            Package::PREMIUM => [
                'setting' => ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
                'value' => '1',
            ],
            Package::CEP_FREE => [
                'setting' => ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
                'value' => '0',
            ],
            Package::ICONTROL => [
                'setting' => ModuleType::INVOICES_REVERSE_CHARGE_ENABLED,
                'value' => '0',
            ],
        ]);
    }
}
