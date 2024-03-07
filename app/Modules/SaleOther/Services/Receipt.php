<?php

namespace App\Modules\SaleOther\Services;

use App\Modules\CashFlow\Events\ReceiptWasCreated;
use Illuminate\Contracts\Events\Dispatcher as Event;
use App\Models\Db\CompanyService;
use App\Models\Db\Receipt as ModelReceipt;
use App\Models\Db\ReceiptItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;
use App\Models\Db\VatRate;
use App\Models\Db\User;
use App\Models\Db\PaymentMethod;
use Carbon\Carbon;

class Receipt
{
    /**
     * @var ReceiptItem
     */
    protected $receipt_item;

    /**
     * @var CompanyService
     */
    protected $company_service;

    /**
     * @var ModelReceipt
     */
    protected $receipt;

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
     * Receipt constructor.
     *
     * @param Connection $db
     * @param ModelReceipt $receipt
     * @param ReceiptItem $receipt_item
     * @param CompanyService $company_service
     * @param VatRate $vat_rate
     * @param PaymentMethod $payment_method
     */
    public function __construct(
        Connection $db,
        ModelReceipt $receipt,
        ReceiptItem $receipt_item,
        CompanyService $company_service,
        VatRate $vat_rate,
        PaymentMethod $payment_method,
        Event $event
    ) {
        $this->db = $db;
        $this->receipt_item = $receipt_item;
        $this->receipt = $receipt;
        $this->company_service = $company_service;
        $this->vat_rate = $vat_rate;
        $this->payment_method = $payment_method;
        $this->event = $event;
    }

    /**
     * Create receipt.
     *
     * @param Request $request
     * @param User $user
     *
     * @return mixed
     */
    public function create(Request $request, User $user)
    {
        return $this->db->transaction(function () use ($request, $user) {
            $receipt = $this->createReceipt($request, $user);
            $this->createReceiptItems($receipt, $request, $user);
            $this->event->dispatch(new ReceiptWasCreated($receipt, $request));

            return $receipt;
        });
    }

    /**
     * Create new receipt items in database.
     *
     * @param ModelReceipt $receipt
     * @param Request $request
     * @param User $user
     */
    public function createReceiptItems(ModelReceipt $receipt, Request $request, User $user)
    {
        $items = collect($request->input('items'));

        $items->each(function ($item) use ($receipt, $user) {
            $company_service = $this->findCompanyService($item['name'], $user);
            if (empty($company_service)) {
                $company_service = $this->company_service->create([
                    'company_id' => $user->getSelectedCompanyId(),
                    'name' => trim($item['name']),
                    'vat_rate_id' => $this->vat_rate->findByName($item['vat_rate'])->id,
                    'creator_id' => $user->id,
                ]);
            }

            $this->receipt_item->create([
                'receipt_id' => $receipt->id,
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
                'creator_id' => $user->id,
            ]);

            $company_service->increment('is_used');
            $company_service->save();
        });
    }

    /**
     * @param Request $request
     * @param User $user
     * @param Builder|null $base_query
     *
     * @return mixed
     */
    public function filterReceipt(Request $request, User $user, $base_query = null)
    {
        if ($base_query === null) {
            $base_query = $this->receipt;
        }

        $receipts_query = $base_query->inCompany($user);

        if ($request->input('user_id')) {
            $receipts_query->where('user_id', $request->input('user_id'));
        }

        if ($request->input('date_start')) {
            $receipts_query->whereDate('sale_date', '>=', $request->input('date_start'));
        }

        if ($request->input('date_end')) {
            $receipts_query->whereDate('sale_date', '<=', $request->input('date_end'));
        }
        if ($request->input('payment_method_id')) {
            $receipts_query->where('payment_method_id', $request->input('payment_method_id'));
        }

        if ($request->input('transaction_number')) {
            $receipts_query->where(
                'transaction_number',
                'like',
                '%' . trim(mb_strtolower($request->input('transaction_number'))) . '%'
            );
        }

        if ($request->input('number')) {
            $receipts_query->where(
                'number',
                'like',
                '%' . trim(mb_strtolower($request->input('number'))) . '%'
            );
        }

        if ($request->input('year')) {
            if ($request->input('month')) {
                $start_date = Carbon::create($request->input('year'), $request->input('month'))
                    ->firstOfMonth();
                $end_date =
                    Carbon::create($request->input('year'), $request->input('month'))->endOfMonth();
            } else {
                $start_date = Carbon::create($request->input('year'))->firstOfYear();
                $end_date = Carbon::create($request->input('year'))->endOfYear();
            }
            $receipts_query->whereDate('sale_date', '>=', $start_date)
                ->whereDate('sale_date', '<=', $end_date);
        }

        if ($request->input('no_invoice') !== null && $request->input('no_invoice')) {
            $receipts_query->doesntHave('invoices');
        }

        return $receipts_query;
    }

    /**
     * Get summary for receipt items.
     *
     * @param Request $request
     *
     * @return Collection
     */
    public function getReceiptItemsSummary(Request $request)
    {
        $columns = [
            'name',
            'price_gross',
            'vat_rate',
            'sum(quantity) AS quantity',
            'sum(price_net_sum) AS price_net_sum',
            'sum(price_gross_sum) AS price_gross_sum',
            'SUM(vat_sum) as vat_sum',
        ];

        return ReceiptItem::whereHas('receipt', function ($q) use ($request, $columns) {
            $this->filterReceipt($request, auth()->user(), $q);
        })->selectRaw(implode(', ', $columns))
            ->groupBy('name', 'price_gross', 'vat_rate')
            ->orderBy('name', 'ASC')
            ->orderBy('vat_rate', 'ASC')
            ->orderBy('price_gross', 'DESC')->get();
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
        return $this->receipt->inCompany($user)
                ->where('transaction_number', $request->input('transaction_number'))
                ->count() > 0;
    }

    public function getParams(Request $request)
    {
        // @todo this is probably not the best place for those, because this is used for pdf (view)
        $params = [];
        if ($request->input('date_start')) {
            $params['date_start'] = $request->input('date_start');
        }
        if ($request->input('date_end')) {
            $params['date_end'] = $request->input('date_end');
        }
        if ($request->input('transaction_number')) {
            $params['transaction_number'] = $request->input('transaction_number');
        }
        if ($request->input('number')) {
            $params['number'] = $request->input('number');
        }
        if ($request->input('payment_method_id')) {
            $params['payment_method'] =
                PaymentMethod::findOrFail($request->input('payment_method_id'));
        }
        if ($request->input('user_id')) {
            $params['user'] = User::findOrFail($request->input('user_id'));
        }

        return $params;
    }

    /**
     * Create new receipt in database.
     *
     * @param Request $request
     * @param User $user
     *
     * @return ModelReceipt
     */
    protected function createReceipt(Request $request, User $user)
    {
        return $this->receipt->create([
            'number' => $request->input('number'),
            'transaction_number' => $request->input('transaction_number'),
            'user_id' => $user->id,
            'company_id' => $user->getSelectedCompanyId(),
            'sale_date' => $request->input('sale_date'),
            'price_net' => normalize_price($request->input('price_net')),
            'price_gross' => normalize_price($request->input('price_gross')),
            'vat_sum' => normalize_price($request->input('vat_sum')),
            'payment_method_id' => $this->payment_method::findBySlug($request->input('payment_method'))->id,
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
