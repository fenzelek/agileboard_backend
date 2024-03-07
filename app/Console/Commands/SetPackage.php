<?php

namespace App\Console\Commands;

use App\Models\Db\Company;
use App\Models\Db\Package;
use App\Modules\Company\Services\CompanyModuleUpdater;
use Illuminate\Console\Command;

class SetPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-package {package} {company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set package in selected company';

    private $updater;

    /**
     * Create a new command instance.
     *
     * @param CompanyModuleUpdater $updater
     */
    public function __construct(CompanyModuleUpdater $updater)
    {
        parent::__construct();

        $this->updater = $updater;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $package = $this->argument('package');
        $company_id = $this->argument('company');

        if (! in_array($package, ['default', 'enterprise'])) {
            $this->error('Wrong command');

            return;
        }

        $company = Company::find($company_id);

        if (! $company) {
            $this->error('Wrong command');

            return;
        }

        if ($package == 'default') {
            if ($company->realPackage()->id != Package::findDefault()->id) {
                $this->setPackage($company, Package::findDefault());
            }

            $this->info('Company ' . $company_id . ' has changed package to default.');
        } else {
            if ($company->realPackage()->id != Package::findBySlug(Package::CEP_ENTERPRISE)->id) {
                $this->setPackage($company, Package::findBySlug(Package::CEP_ENTERPRISE));
            }

            $this->info('Company ' . $company_id . ' has changed package to enterprise.');
        }
    }

    private function setPackage(Company $company, Package $package)
    {
        $modules = $package->modules()->with(['mods' => function ($q) use ($package) {
            $q->whereHas('modPrices', function ($q) use ($package) {
                $q->default('PLN');
                $q->where('package_id', $package->id);
            });
            $q->with(['modPrices' => function ($q) use ($package) {
                $q->default('PLN');
                $q->where('package_id', $package->id);
            }]);
        }])->get();

        $this->updater->setCompany($company);
        $transaction = $this->updater->createHistory($modules);
        $this->updater->activateWithUpdateHistory($transaction->id);
    }
}
