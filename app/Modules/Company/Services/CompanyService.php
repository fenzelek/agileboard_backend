<?php

namespace App\Modules\Company\Services;

use Illuminate\Contracts\Auth\Guard;
use App\Models\Db\CompanyService as CompanyServiceModel;
use Illuminate\Http\Request;

class CompanyService
{
    /**
     * @var CompanyServiceModel
     */
    protected $company_service;

    /**
     * Company constructor.
     *
     * @param CompanyServiceModel $company
     */
    public function __construct(CompanyServiceModel $company_service)
    {
        $this->company_service = $company_service;
    }

    /**
     * Create new company service.
     *
     * @param Request $request
     * @param Guard $auth
     *
     * @return CompanyServiceModel
     */
    public function create(Request $request, Guard $auth)
    {
        return $this->company_service->create(array_merge(
            $this->parseIncomingData($request->all()),
            [
                    'company_id' => $auth->user()->getSelectedCompanyId(),
                    'creator_id' => $auth->id(),
                ]
        ));
    }

    /**
     * Update company service.
     *
     * @param CompanyServiceModel $company_service
     * @param Request $request
     * @param Guard $auth
     */
    public function update(CompanyServiceModel $company_service, Request $request, Guard $auth)
    {
        $company_service->update(array_merge(
            $this->parseIncomingData($request->all()),
            [
                'editor_id' => $auth->id(),
            ]
        ));
    }

    /**
     * Trim incoming data.
     *
     * @param array $service_data
     * @return array
     */
    protected function parseIncomingData(array $service_data)
    {
        $selectedFields = [
            'name',
            'type',
            'price_net',
            'price_gross',
            'print_on_invoice',
            'description',
            'vat_rate_id',
            'service_unit_id',
            'pkwiu',
        ];

        return collect($service_data)->only($selectedFields)->map(function ($value, $key) {
            if (in_array($key, ['price_net', 'price_gross'])) {
                return normalize_price($value);
            }

            return trimInput($value);
        })->all();
    }
}
