<?php

use App\Models\Db\Package;
use Illuminate\Database\Migrations\Migration;

class AddInitialPackages extends Migration
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
                Package::create($data + ['slug' => $slug]);
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
                $package = Package::where('slug', $slug)->first();
                if ($package) {
                    $package->forceDelete();
                }
            });
        });
    }

    /**
     * Get packages to create.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function packages()
    {
        return collect([
            Package::START => [
                'public' => true,
                'name' => 'Start',
            ],
            Package::PREMIUM => [
                'public' => true,
                'name' => 'Premium',
            ],
            Package::CEP_FREE => [
                'public' => false,
                'name' => 'CEP',
            ],
            Package::ICONTROL => [
                'public' => false,
                'name' => 'Icontrol',
            ],
        ]);
    }
}
