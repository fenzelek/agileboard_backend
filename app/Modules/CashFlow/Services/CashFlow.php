<?php

namespace App\Modules\CashFlow\Services;

use App\Models\Other\Paginated;
use App\Services\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Db\User;
use App\Models\Db\CashFlow as ModelCashFlow;
use Illuminate\Support\Collection as SupportCollection;

class CashFlow
{
    /**
     * @var Paginator
     */
    protected $paginator;

    /**
     * CashFlow constructor.
     *
     * @param Paginator $paginator
     */
    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public function filterCashFlow(Request $request, User $user)
    {
        $cash_flow_query = ModelCashFlow::inCompany($user);

        if ($request->input('user_id')) {
            $cash_flow_query->where('user_id', $request->input('user_id'));
        }
        if ($request->input('date')) {
            $cash_flow_query->where('flow_date', $request->input('date'));
        }

        if ($request->input('cashless') !== null) {
            $cash_flow_query->where('cashless', $request->input('cashless'));
        }

        return $cash_flow_query;
    }

    /**
     * Get balanced cash flows.
     *
     * @param Request $request
     * @param User $user
     * @param bool $paginated
     *
     * @return Paginated
     */
    public function getBalanced(Request $request, User $user, $paginated = true)
    {
        // first we apply query filters same as without balancing
        $filtered_query = $this->filterCashFlow($request, $user);

        // get raw records that should be balanced
        $balanced = $this->getBalancedRawRecords($filtered_query, $request, $paginated);

        $ids = ($paginated ? $balanced->items()->pluck('id')->all() : $balanced->pluck('id')->all());

        // now we get cash flows that should be displayed (their ids are the same as balanced ids)
        $cash_flows = ModelCashFlow::whereIn('id', $ids)->with('receipt', 'invoice')
            ->orderBy('id', 'ASC')->get();

        // now for each cash flow we add balanced data
        $this->addBalanced($cash_flows, $paginated ? $balanced->items() : $balanced);

        // no pagination, return record directly
        if (! $paginated) {
            return $cash_flows;
        }

        // otherwise return paginated object - everything is same except items
        return new Paginated(
            $cash_flows,
            $balanced->total(),
            $balanced->pages(),
            $balanced->page(),
            $balanced->limit()
        );
    }

    // @todo this method should be in SaleReport module
    public function filterCashFlowReportSummary(Request $request, User $user)
    {
        $cash_flows_initial_sum = $this->filterCashFlow($request, $user)
            ->where('direction', 'initial')->sum('amount');
        $cash_flows_in_sum = $this->filterCashFlow($request, $user)
            ->where('direction', 'in')->sum('amount');
        $cash_flows_out_sum = $this->filterCashFlow($request, $user)
            ->where('direction', 'out')->sum('amount');
        $cash_flows_final_sum = $this->filterCashFlow($request, $user)
            ->where('direction', 'final')->sum('amount');

        $final_sum = $cash_flows_initial_sum + $cash_flows_in_sum - $cash_flows_out_sum;

        $result = [
            'cash_initial_sum' => $this->undoNormalizeAmount($cash_flows_initial_sum),
            'cash_in_sum' => $this->undoNormalizeAmount($cash_flows_in_sum),
            'cash_out_sum' => $this->undoNormalizeAmount($cash_flows_out_sum),
            'cash_final_sum' => $this->undoNormalizeAmount($cash_flows_final_sum),
            'calc_final_sum' => $this->undoNormalizeAmount($final_sum),
            'equals_final_sum' => ($cash_flows_final_sum == $final_sum) ? true : false,
        ];

        return $result;
    }

    public function undoNormalizeAmount($amount)
    {
        return denormalize_price($amount);
    }

    /**
     * Add balanced information into cash flows.
     *
     * @param Collection $cash_flows
     * @param SupportCollection $balanced_data
     */
    protected function addBalanced(Collection $cash_flows, SupportCollection $balanced_data)
    {
        $cash_flows->each(function ($record) use ($balanced_data) {
            $record->balanced_summary = $balanced_data->first(function ($balanced) use ($record) {
                return $balanced->id == $record->id;
            });
        });
    }

    /**
     * Get balanced raw records.
     *
     * @param Builder $query
     * @param Request $request
     * @param bool $paginated
     *
     * @return Paginated|SupportCollection
     */
    protected function getBalancedRawRecords(Builder $query, Request $request, $paginated = true)
    {
        // get base query
        $base_query = $this->getBaseQuery($query);

        // calculate limit and current page number
        $limit = $this->paginator->getLimit($request->input('limit', 50));

        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        if ($paginated) {
            $limit_sql = ' LIMIT ?, ?';
            $limit_bindings = [($page - 1) * $limit, $limit];
        } else {
            $limit_sql = '';
            $limit_bindings = [];
        }

        // get balanced data
        $balanced = \DB::select(
            'SELECT * FROM (' .
            \DB::raw($base_query->toSql() . ') a  ORDER BY id ASC' . $limit_sql),
            array_merge($base_query->getBindings(), $limit_bindings)
        );

        // if we want to get records without pagination, we just want to get the data
        if (! $paginated) {
            return collect($balanced);
        }

        // otherwise when paginated, need to get some more data

        // get total number of records
        $total = \DB::select('SELECT count(id) AS nr FROM (' . \DB::raw($base_query->toSql())
            . ') a', $base_query->getBindings());
        $total = $total[0]->nr;

        // calculate number of pages
        $pages = ceil($total / $limit);

        // return data as Paginated object
        return new Paginated(collect($balanced), $total, $pages, $page, $limit);
    }

    /**
     * Get base query to get balanced data.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    protected function getBaseQuery(Builder $query)
    {
        // get queries to union
        $queries = $this->getQueries($query);

        // run union on queries
        $base_query = $queries->shift();
        $queries->each(function ($query) use ($base_query) {
            $base_query->union($query);
        });

        return $base_query;
    }

    /**
     * Get queries that will be used for building main query.
     *
     * @param Builder $query
     *
     * @return SupportCollection
     */
    protected function getQueries(Builder $query)
    {
        $columns = [
            'MIN(id) as id',
            'cashless',
            "'in_or_out'",
            'count(*) AS balanced_records',
            "sum(
            CASE direction
             WHEN 'in' THEN amount
             WHEN 'out' THEN amount * -1 
            END
            ) AS sum",
        ];

        // for 1st query we get records with receipt_id filled in (in and out only)
        $receipt_query = clone $query;
        $receipt_query->selectRaw(implode(', ', $columns))
            ->whereIn('direction', [ModelCashFlow::DIRECTION_IN, ModelCashFlow::DIRECTION_OUT])
            ->whereNotNull('receipt_id')->groupBy(['receipt_id', 'cashless']);

        // for 2nd query we get records with invoice_id filled in (in and out only)
        $invoice_query = clone $query;
        $invoice_query->selectRaw(implode(', ', $columns))
            ->whereIn('direction', [ModelCashFlow::DIRECTION_IN, ModelCashFlow::DIRECTION_OUT])
            ->whereNotNull('invoice_id')->groupBy(['invoice_id', 'cashless']);

        // for 3rd query we get records with empty both receipt_id and invoice_id and records
        // that are not in or out
        $others_query = clone $query;
        $others_query->selectRaw(implode(', ', [
            'id',
            'cashless',
            'direction',
            '1 AS balanced_records',
            'amount AS sum',
        ]))->where(function ($q) {
            $q->whereNotIn(
                'direction',
                [ModelCashFlow::DIRECTION_IN, ModelCashFlow::DIRECTION_OUT]
            )
                ->orWhere(function ($q) {
                    $q->whereNull('receipt_id')->whereNull('invoice_id');
                });
        });

        return collect([$receipt_query, $invoice_query, $others_query]);
    }
}
