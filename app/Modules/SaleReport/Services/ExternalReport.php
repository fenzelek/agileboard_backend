<?php

namespace App\Modules\SaleReport\Services;

use App\Models\Db\User;
use App\Modules\SaleReport\Services\Contracts\ExternalExportProvider;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class ExternalReport
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Report
     */
    protected $report;

    /**
     * ExternalReport constructor.
     *
     * @param Application $app
     * @param Report $report
     */
    public function __construct(Application $app, Report $report)
    {
        $this->app = $app;
        $this->report = $report;
    }

    /**
     * @param $export_name
     *
     * @return ExternalExportProvider
     * @throws \Throwable
     */
    public function getProvider($export_name)
    {
        $class = $this->getProviderClassName($export_name);

        throw_unless(
            class_exists($class),
            new Exception('Wrong application setting for export provider.')
        );

        return $this->app->make($class);
    }

    /**
     * @param Request $request
     * @param User $user
     *
     * @return mixed
     */
    public function getInvoices(Request $request, User $user)
    {
        return $this->report->filterInvoicesRegistry($request, $user)
            ->orderBy('issue_date', 'asc')
            ->orderBy('id')
            ->with([
                'contractor',
                'payments',
            ])->get();
    }

    /**
     * Get provider class name.
     *
     * @param string $export_name
     *
     * @return string
     */
    protected function getProviderClassName($export_name)
    {
        return __NAMESPACE__ . '\\' . studly_case($export_name);
    }
}
