<?php

namespace App\Modules\SaleInvoice\Services;

use App\Http\Requests\Request;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceRegistry;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Db\User;
use Carbon\Carbon;
use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceType as ModelInvoiceType;
use Illuminate\Support\Collection;

class InvoiceNumber
{
    /**
     * @var ModelInvoice
     */
    protected $invoice;

    /**
     * @var ModelInvoiceType
     */
    protected $invoice_type;

    /**
     * @var InvoiceFormat
     */
    protected $invoice_format;

    /**
     * InvoiceNumber constructor.
     *
     * @param ModelInvoice $invoice
     * @param ModelInvoiceType
     */
    public function __construct(
        ModelInvoice $invoice,
        ModelInvoiceType $invoice_type,
        InvoiceFormat $invoice_format
    ) {
        $this->invoice = $invoice;
        $this->invoice_type = $invoice_type;
        $this->invoice_format = $invoice_format;
    }

    /**
     * Find first empty order number.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param User $user
     *
     * @return int
     */
    public function findFirstEmptyOrderNumber(Request $request, InvoiceRegistry $registry, User $user)
    {
        if ($this->canUseStartNumber($request, $registry)) {
            return $registry->start_number;
        }

        $years_billing = mb_strpos($registry->invoiceFormat->format, '{%Y}');
        $monthly_billing = mb_strpos($registry->invoiceFormat->format, '{%m}');
        $query_last_order_number = $this->invoice->withTrashed()->inCompany($user)
            ->whereIn('invoice_type_id', $this->getTypes($request->input('invoice_type_id'))->pluck('id'))
            ->where('invoice_registry_id', $registry->id);
        if ($years_billing) {
            if ($monthly_billing) {
                $start_date = Carbon::parse($request->input('issue_date'))->firstOfMonth();
                $end_date = Carbon::parse($request->input('issue_date'))->lastOfMonth();
            } else {
                $start_date = Carbon::parse($request->input('issue_date'))->firstOfYear();
                $end_date = Carbon::parse($request->input('issue_date'))->lastOfYear();
            }

            $query_last_order_number->whereDate('order_number_date', '>=', $start_date->toDateString())
                ->whereDate('order_number_date', '<=', $end_date->toDateString());
        }

        $last_order_number = $query_last_order_number->max('order_number');
        if (empty($last_order_number)) {
            return 1;
        }

        return ++$last_order_number;
    }

    /**
     * Parse new number invoice.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     * @param $order_number
     *
     * @return string
     */
    public function parseInvoiceNumber(Request $request, InvoiceRegistry $registry, $order_number)
    {
        $number_participant = [];
        $invoice_type_id = $request->input('invoice_type_id');
        if ($this->isSubtypeOf(InvoiceTypeStatus::CORRECTION, $invoice_type_id)) {
            $number_participant[] = 'KOR';
        } elseif ($this->isSubtypeOf(InvoiceTypeStatus::PROFORMA, $invoice_type_id)) {
            $number_participant[] = 'PRO';
        } elseif ($this->isSubtypeOf(InvoiceTypeStatus::ADVANCE, $invoice_type_id)) {
            $number_participant[] = 'ZAL';
        } elseif ($this->isSubtypeOf(InvoiceTypeStatus::ADVANCE_CORRECTION, $invoice_type_id)) {
            $number_participant[] = 'KOR/ZAL';
        }

        if (! empty($registry->prefix)) {
            $number_participant[] = $registry->prefix;
        }
        $search = ['{%nr}', '{%m}', '{%Y}'];
        $replace = [
            $order_number,
            Carbon::parse($request->input('issue_date'))->format('m'),
            Carbon::parse($request->input('issue_date'))->format('Y'),
        ];

        $number_participant[] = str_replace($search, $replace, $registry->invoiceFormat->format);

        return implode('/', $number_participant);
    }

    /**
     * Get type and subtype invoice types interactive with incoming invoice type id.
     *
     * @param $invoice_type_id
     * @return int
     *
     * @return Collection
     */
    protected function getTypes($invoice_type_id)
    {
        $invoice_type = $this->invoice_type->find($invoice_type_id);

        return $this->invoice_type->where(function ($query) use ($invoice_type) {
            $query->where('id', $invoice_type->id)
                ->orWhere('parent_type_id', $invoice_type->id);
            if (! empty($invoice_type->parent_type_id)) {
                $query->orWhere('id', $invoice_type->parent_type_id)
                    ->orWhere('parent_type_id', $invoice_type->parent_type_id);
            }
        })->get();
    }

    /**
     * Check if  group of types contain invoice type id.
     *
     * @param $slug
     * @param $invoice_type_id
     *
     * @return bool
     */
    protected function isSubtypeOf($slug, $invoice_type_id)
    {
        return $this->getTypeSlugs($invoice_type_id)->contains($slug);
    }

    /**
     * Get Slug collection of types.
     *
     * @param $invoice_type_id
     *
     * @return Collection
     */
    protected function getTypeSlugs($invoice_type_id)
    {
        return $this->getTypes($invoice_type_id)->pluck('slug');
    }

    /**
     * Check if start number from registry can be used.
     *
     * @param Request $request
     * @param InvoiceRegistry $registry
     *
     * @return bool
     */
    protected function canUseStartNumber(Request $request, InvoiceRegistry $registry)
    {
        return $this->invoice_type->findBySlug(InvoiceTypeStatus::VAT)->id ==
            $request->input('invoice_type_id') && $registry->start_number &&
            $registry->invoice_format_id == $this->invoice_format
                ->findByFormatStrict(InvoiceFormat::YEARLY_FORMAT)->id &&
            ! $registry->is_used;
    }
}
