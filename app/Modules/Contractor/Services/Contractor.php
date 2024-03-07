<?php

namespace App\Modules\Contractor\Services;

use App\Models\Db\ContractorAddress;
use App\Models\Db\User;
use App\Models\Db\Contractor as ContractorModel;
use App\Models\Other\ModuleType;
use App\Models\Other\ContractorAddressType;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use App\Services\Paginator;
use Illuminate\Support\Collection;

class Contractor
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var ContractorModel
     */
    protected $contractor;

    /**
     * @var ContractorAddress
     */
    protected $contractor_address;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * Company constructor.
     *
     * @param Application $app
     * @param ContractorModel $contractor
     */
    public function __construct(
        Application $app,
        ContractorModel $contractor,
        Connection $db,
        ContractorAddress $contractor_address
    ) {
        $this->app = $app;
        $this->contractor = $contractor;
        $this->db = $db;
        $this->contractor_address = $contractor_address;
    }

    /**
     * Get list contractors with pagination.
     *
     * @param Paginator $paginator
     * @param User $user
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Paginator $paginator, User $user, Request $request)
    {
        $contractors = $this->contractor
            ->selectRaw(implode(', ', $this->getColumnsForIndexQuery()))
            ->where('contractors.company_id', $user->getSelectedCompanyId())
            ->where(function ($q) use ($request) {
                if ($request->has('search') && $request->search != '') {
                    $q->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('vatin', 'LIKE', '%' . $request->search . '%');
                }
            });
        // If delivery addresses is enabled join addresses to contractors
        if ($user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED)
        ) {
            $contractors = $contractors->with('addresses');
        }

        return $paginator->get($contractors->with('vatinPrefix'), 'contractors.index');
    }

    /**
     * Create new contractor.
     *
     * @param User $user
     * @param Request $request
     *
     * @return static
     */
    public function create(User $user, Request $request)
    {
        $contractor = $this->contractor->newInstance();
        $contractor = $this->setValues($contractor, $request);
        $contractor->company_id = $user->getSelectedCompanyId();
        $contractor->creator_id = $user->id;

        return $this->db->transaction(function () use ($contractor, $request, $user) {
            $contractor->save();
            $this->addExtraAddresses($request, $contractor, $user);

            return $contractor;
        });
    }

    /**
     * Parsing data for storing in database.
     *
     * @param $address
     * @return array
     */
    public function parseAddressDetails($address): array
    {
        $trimming_data = $this->trimIncomingAddressDetails($address);

        return  $trimming_data + [
                'name' => $this->contractor_address::getDefaultName($trimming_data),
            ];
    }

    /**
     * Update contractor.
     *
     * @param User $user
     * @param Request $request
     * @param $id
     *
     * @return mixed
     */
    public function update(User $user, Request $request, $id)
    {
        $contractor = $this->contractor->inCompany($user)->findOrFail($id);
        $contractor = $this->setValues($contractor, $request);
        $contractor->editor_id = $user->id;

        return $this->db->transaction(function () use ($user, $request, $contractor) {
            $contractor->save();
            if ($this->canAddExtraAddresses($user)) {
                $this->updateExtraAddresses($request, $contractor, $user);
            }

            return $contractor;
        });
    }

    /**
     * Get one contractor.
     *
     * @param User $user
     * @param $id
     *
     * @return mixed
     */
    public function show(User $user, $id)
    {
        //todo update this test for sys-121
        return $this->contractor->selectRaw('contractors.*, 0 AS payments_all, 0 AS payments_paid, 0 AS payments_paid_late, 0 AS payments_not_paid')
            ->with(['addresses', 'vatinPrefix'])
            ->inCompany($user)->findOrFail($id);
    }

    /**
     * Delete selected contractor.
     *
     * @param User $user
     * @param $id
     *
     * @return mixed
     */
    public function destroy(User $user, $id)
    {
        $contractor = $this->contractor->inCompany($user)->where('is_used', 0)->findOrFail($id);

        $contractor->remover_id = $user->id;
        $contractor->deleted_at = Carbon::now();

        return $contractor->save();
    }

    /**
     * Validates polish zip codes length.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validatePolishZipCode($attribute, $value, $parameters)
    {
        foreach ($value as $address) {
            if (empty($address)) {
                continue;
            }
            if (! $this->checkPolishZipCode($address)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check polish zip code.
     *
     * @param $address
     *
     * @return bool
     */
    protected function checkPolishZipCode($address)
    {
        if (isset($address['country']) &&
            $address['country'] == 'Polska' &&
            mb_strlen($address['zip_code']) > 7) {
            return false;
        }

        return true;
    }

    /**
     * Get columns for index query.
     *
     * @return array
     */
    protected function getColumnsForIndexQuery()
    {
        // might need tuning in future for performance reasons to joins
        return [
            'contractors.*',
            '(SELECT SUM(price_gross) FROM invoices 
                  WHERE contractors.id = invoices.contractor_id) 
                  AS payments_all',
            '(SELECT SUM(price_gross) FROM invoices 
                  WHERE contractors.id = invoices.contractor_id AND paid_at IS NOT NULL) 
                  AS payments_paid',
            '(SELECT SUM(price_gross) FROM invoices 
                  WHERE contractors.id = invoices.contractor_id AND paid_at IS NOT NULL 
                  AND (DATE(DATE_ADD(issue_date, INTERVAL payment_term_days DAY)) < DATE(paid_at))) 
                  AS payments_paid_late',
            '(SELECT SUM(price_gross) FROM invoices 
                 WHERE contractors.id = invoices.contractor_id AND paid_at IS NULL) 
                 AS payments_not_paid',
        ];
    }

    /**
     * Add extra addresses to contractor.
     *
     * @param Request $request
     * @param ContractorModel $contractor
     */
    protected function addExtraAddresses(Request $request, ContractorModel $contractor, User $user)
    {
        if ($this->canAddExtraAddresses($user)) {
            $extra_addresses = $request->input('addresses');
        } else {
            $extra_addresses = [
                $contractor->getRawMainAddress() + [
                    'type' => ContractorAddressType::DELIVERY,
                    'default' => true,
                ],
            ];
        }
        $this->createExtraAddresses($contractor, collect($extra_addresses));
    }

    /**
     * Create new extra addresses.
     *
     * @param ContractorModel $contractor
     * @param Collection $extra_addresses
     */
    protected function createExtraAddresses(ContractorModel $contractor, Collection $extra_addresses)
    {
        $extra_addresses->each(function ($address) use ($contractor) {
            $contractor->addresses()->create($this->parseAddressDetails($address));
        });
    }

    /**
     * Trim incoming address details.
     *
     * @param array $address
     * @return array
     */
    protected function trimIncomingAddressDetails(array $address)
    {
        $selectedFields = [
            'type',
            'street',
            'number',
            'zip_code',
            'city',
            'country',
            'default',
        ];

        return collect($address)->only($selectedFields)->map(function ($value) {
            return trimInput($value);
        })->all();
    }

    /**
     * Update extra addresses to contractor.
     *
     * @param Request $request
     * @param ContractorModel $contractor
     * @param User $user
     */
    protected function updateExtraAddresses(Request $request, ContractorModel $contractor, User $user)
    {
        $extra_addresses = collect($request->input('addresses'));

        $contractor->addresses()
            ->whereNotIn('id', $extra_addresses->where('id', '!==', null)->pluck('id'))
            ->delete();
        $extra_addresses->each(function ($address, $key) use ($contractor, $extra_addresses) {
            if (array_has($address, 'id')) {
                $current_address = $this->contractor_address->find($address['id']);
                $current_address->update($this->parseAddressDetails($address));
                $extra_addresses->forget($key);
            }
        });
        $this->createExtraAddresses($contractor, $extra_addresses);
    }

    /**
     * Can add extra Addresses.
     * @param User $user
     * @return bool
     */
    protected function canAddExtraAddresses(User $user)
    {
        return $user->selectedCompany()->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED);
    }

    /**
     * @param $contractor
     * @param $request
     *
     * @return mixed
     */
    private function setValues($contractor, $request)
    {
        $fields = [
            'name',
            'country_vatin_prefix_id',
            'vatin',
            'email',
            'phone',
            'bank_name',
            'bank_account_number',
            'main_address_street',
            'main_address_number',
            'main_address_zip_code',
            'main_address_city',
            'main_address_country',
            'contact_address_street',
            'contact_address_number',
            'contact_address_zip_code',
            'contact_address_city',
            'contact_address_country',
        ];

        collect($fields)->each(function ($field) use ($request, $contractor) {
            $contractor->$field = trimInput($request->input($field));
        });

        //if not send then default
        $contractor->default_payment_term_days = $request->input('default_payment_term_days', null);
        $contractor->default_payment_method_id = $request->input('default_payment_method_id', null);

        return $contractor;
    }
}
