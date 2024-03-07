<?php

use App\Models\Db\Package;
use App\Models\Other\ModuleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMultipleUsersSettingToPackages extends Migration
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

                if (Schema::hasTable('application_settings')) {
                    $package->applicationSettings()->detach(DB::table('application_settings')->where(
                        'slug',
                        ModuleType::GENERAL_MULTIPLE_USERS
                    )->first()->id);
                }
            });
        });
    }

    protected function packages()
    {
        return collect([
            Package::START => [
                ModuleType::GENERAL_MULTIPLE_USERS => '0',
            ],
            Package::PREMIUM => [
                ModuleType::GENERAL_MULTIPLE_USERS => '1',
            ],
            Package::CEP_FREE => [
                ModuleType::GENERAL_MULTIPLE_USERS => '1',
            ],
            Package::ICONTROL => [
                ModuleType::GENERAL_MULTIPLE_USERS => '1',
            ],
        ]);
    }
}
