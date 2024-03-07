<?php

use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddJpkSettingToPackages extends Migration
{
    /**
     * Run the migrations.
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

                $settings = collect($data)->map(function ($value, $slug) {
                    return [
                        'id' => DB::table('application_settings')->where('slug', $slug)->first()->id,
                        'value' => $value,
                    ];
                })->keyBy('id')->map(function ($value) {
                    return array_except($value, 'id');
                })->all();

                $package->applicationSettings()->attach($settings);
            });
        });
    }

    /**
     * Reverse the migration.
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

                if (Schema::hasTable('application_settings')) {
                    $package->applicationSettings()->detach(DB::table('application_settings')->where(
                        'slug',
                        ModuleType::INVOICES_JPK_EXPORT
                    )->first()->id);
                }
            });
        });
    }

    protected function packages()
    {
        return collect([
            Package::START => [
                ModuleType::INVOICES_JPK_EXPORT => '0',
            ],
            Package::PREMIUM => [
                ModuleType::INVOICES_JPK_EXPORT => '1',
            ],
            Package::CEP_FREE => [
                ModuleType::INVOICES_JPK_EXPORT => '0',
            ],
            Package::ICONTROL => [
                ModuleType::INVOICES_JPK_EXPORT => '0',
            ],
        ]);
    }
}
