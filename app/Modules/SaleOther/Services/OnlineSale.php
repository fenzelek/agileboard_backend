<?php

namespace App\Modules\SaleOther\Services;

use App\Models\Db\CompanyService;
use App\Models\Db\OnlineSale as ModelOnlineSale;
use App\Models\Db\OnlineSaleItem;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use App\Models\Db\VatRate;
use App\Models\Db\User;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\PaymentMethod;
use Carbon\Carbon;

class OnlineSale
{
    /**
     * @var OnlineSaleItem
     */
    protected $online_sale_item;

    /**
     * @var CompanyService
     */
    protected $company_service;

    /**
     * @var ModelOnlineSale
     */
    protected $online_sale;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var VatRate
     */
    protected $vat_rate;

    /**
     * @var PaymentMethod
     */
    protected $payment_method;

    /**
     * OnlineSale constructor.
     *
     * @param Connection $db
     * @param ModelOnlineSale $online_sale
     * @param OnlineSaleItem $online_sale_item
     * @param CompanyService $company_service
     * @param VatRate $vat_rate
     * @param PaymentMethod $payment_method
     */
    public function __construct(
        Connection $db,
        ModelOnlineSale $online_sale,
        OnlineSaleItem $online_sale_item,
        CompanyService $company_service,
        VatRate $vat_rate,
        PaymentMethod $payment_method
    ) {
        $this->db = $db;
        $this->online_sale_item = $online_sale_item;
        $this->online_sale = $online_sale;
        $this->company_service = $company_service;
        $this->vat_rate = $vat_rate;
        $this->payment_method = $payment_method;
    }

    /**
     * Create online sale.
     *
     * @param Request $request
     * @param User $user
     *
     * @return ModelOnlineSale
     */
    public function create(Request $request, User $user)
    {
        return $this->db->transaction(function () use ($request, $user) {
            $online_sale = $this->createOnlineSale($request, $user);
            $this->createOnlineSaleItems($online_sale, $request, $user);

            return $online_sale;
        });
    }

    /**
     * Create new online_sale items in database.
     *
     * @param ModelOnlineSale $online_sale
     * @param Request $request
     * @param User $user
     */
    public function createOnlineSaleItems(ModelOnlineSale $online_sale, Request $request, User $user)
    {
        $items = collect($request->input('items'));

        $items->each(function ($item) use ($online_sale, $user) {
            $company_service = $this->findCompanyService($item['name'], $user);
            if (empty($company_service)) {
                $company_service = $this->company_service->create([
                    'company_id' => $user->getSelectedCompanyId(),
                    'name' => trim($item['name']),
                    'vat_rate_id' => $this->vat_rate->findByName($item['vat_rate'])->id,
                    'creator_id' => $user->id,
                ]);
            }

            $this->online_sale_item->create([
                'online_sale_id' => $online_sale->id,
                'company_service_id' => $company_service->id,
                'name' => $item['name'],
                'price_net' => normalize_price($item['price_net']),
                'price_net_sum' => normalize_price($item['price_net_sum']),
                'price_gross' => normalize_price($item['price_gross']),
                'price_gross_sum' => normalize_price($item['price_gross_sum']),
                'vat_rate' => trim($item['vat_rate']),
                'vat_rate_id' => $company_service->vat_rate_id,
                'vat_sum' => normalize_price($item['vat_sum']),
                'quantity' => $item['quantity'],
            ]);

            $company_service->increment('is_used');
            $company_service->save();
        });
    }

    /**
     * Filter online sales query.
     *
     * @param Request $request
     * @param User $user
     *
     * @return mixed
     */
    public function filterOnlineSale(Request $request, User $user)
    {
        $online_sales_query =
            $this->online_sale->inCompany($user);

        if ($request->input('date_start')) {
            $online_sales_query->whereDate('sale_date', '>=', $request->input('date_start'));
        }

        if ($request->input('date_end')) {
            $online_sales_query->whereDate('sale_date', '<=', $request->input('date_end'));
        }

        if ($request->input('transaction_number')) {
            $online_sales_query->where(
                'transaction_number',
                'like',
                '%' . trim(mb_strtolower($request->input('transaction_number'))) . '%'
            );
        }

        if ($request->input('number')) {
            $online_sales_query->where(
                'number',
                'like',
                '%' . trim(mb_strtolower($request->input('number'))) . '%'
            );
        }

        if ($request->input('email')) {
            $online_sales_query->where(
                'email',
                'like',
                '%' . trim(mb_strtolower($request->input('email'))) . '%'
            );
        }

        if ($request->input('year')) {
            if ($request->input('month')) {
                $start_date = Carbon::create($request->input('year'), $request->input('month'))->firstOfMonth();
                $end_date = Carbon::create($request->input('year'), $request->input('month'))->endOfMonth();
            } else {
                $start_date = Carbon::create($request->input('year'))->firstOfYear();
                $end_date = Carbon::create($request->input('year'))->endOfYear();
            }
            $online_sales_query->whereDate('sale_date', '>=', $start_date)
                ->whereDate('sale_date', '<=', $end_date);
        }

        if ($request->input('no_invoice') !== null && $request->input('no_invoice')) {
            $online_sales_query->doesntHave('invoices');
        }

        return $online_sales_query;
    }

    /**
     * Check duplicate transaction number for company.
     *
     * @param Request $request
     * @param User $user
     *
     * @return mixed
     */
    public function isTransactionNumberAlreadyUsed(Request $request, User $user)
    {
        return $this->online_sale->inCompany($user)
            ->where('transaction_number', $request->input('transaction_number'))
            ->count() > 0;
    }

    /**
     * Create new online sale in database.
     *
     * @param Request $request
     * @param User $user
     *
     * @return ModelOnlineSale
     */
    protected function createOnlineSale(Request $request, User $user)
    {
        return $this->online_sale->create([
            'email' => $request->input('email'),
            'number' => $request->input('number'),
            'transaction_number' => $request->input('transaction_number'),
            'company_id' => $user->getSelectedCompanyId(),
            'sale_date' => $request->input('sale_date'),
            'price_net' => normalize_price($request->input('price_net')),
            'price_gross' => normalize_price($request->input('price_gross')),
            'vat_sum' => normalize_price($request->input('vat_sum')),
            'payment_method_id' => $this->payment_method::findBySlug(PaymentMethodType::BANK_TRANSFER)->id,
        ]);
    }

    /**
     * Find company service by name for company.
     *
     * @param $service_name
     * @param User $user
     *
     * @return mixed
     */
    protected function findCompanyService($service_name, User $user)
    {
        return $this->company_service->inCompany($user)->where('name', trim($service_name))
            ->first();
    }
}
