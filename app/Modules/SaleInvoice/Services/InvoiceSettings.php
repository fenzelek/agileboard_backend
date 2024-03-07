<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\Company;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\User;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Http\Request;

class InvoiceSettings
{
    protected $invoice_registry;

    public function __construct(InvoiceRegistry $invoice_registry)
    {
        $this->invoice_registry = $invoice_registry;
    }

    /**
     * Update registries. Update, delete and add new registries.
     *
     * @param Request $request
     * @param Company $company
     * @param User $user
     */
    public function updateRegistries(Request $request, Company $company, User $user)
    {
        $old_registries = $company->registries;
        $new_registries_data = $request->input('invoice_registries');

        // Delete all registries that don't match new registries and are not in use
        $company->registries()
            ->whereNotIn('id', collect($new_registries_data)->where('id', '!==', null)->pluck('id'))
            ->where('is_used', 0)->delete();

        //Update. Registers to be created are moved at the end
        foreach (collect($new_registries_data)->sortByDesc('id') as $registry) {
            if (isset($registry['id']) && $registry['id']) {
                $updated_registry = $old_registries->find($registry['id']);
                $updated_registry->name = trim($registry['name']);
                $updated_registry->default = $registry['default'];
                $updated_registry->editor_id = $user->id;
                if (! $updated_registry->is_used) {
                    $updated_registry->prefix = trim($registry['prefix']);
                    $updated_registry->invoice_format_id = $registry['invoice_format_id'];
                    $updated_registry->start_number = $registry['start_number'];
                }
                $updated_registry->save();
                $old_registries = $old_registries->reject(function ($registry) use ($updated_registry) {
                    return $registry->id == $updated_registry->id;
                });
            } else {
                $this->invoice_registry->create([
                    'invoice_format_id' => $registry['invoice_format_id'],
                    'name' => trim($registry['name']),
                    'prefix' => trim($registry['prefix']),
                    'default' => $registry['default'],
                    'company_id' => $company->id,
                    'start_number' => $registry['start_number'],
                    'editor_id' => $user->id,
                    'creator_id' => $user->id,
                ]);
            }
        }
    }

    /**
     * Custom prefix validation method.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateRegistryPrefix($attribute, $value, $parameters)
    {
        if (! is_array($value)) {
            return false;
        }
        $user = auth()->user();
        $old_registries = InvoiceRegistry::inCompany($user)->get();
        foreach ($value as $request_item) {
            foreach ($old_registries as $registry) {
                if ($request_item['prefix'] == $registry->prefix && (! isset($request_item['id']) ||
                        $request_item['id'] != $registry->id) && $registry->is_used
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validation to test if start number can be added.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateRegistryStartNumber($attribute, $value, $parameters)
    {
        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);
        $register = $this->invoice_registry->find($parameters[0]);
        if ($register != null) {
            if ($register->is_used) {
                return false;
            }

            if ($register->invoice_format_id != $year_format->id) {
                return false;
            }
        }

        if ($parameters[1] != $year_format->id) {
            return false;
        }

        return true;
    }

    /**
     * Check if gross counted setting blocked by issuing any invoices.
     *
     * @param Company $company
     * @param Request $request
     * @return bool
     */
    public function blockedGrossCountedSetting(Company $company, Request $request)
    {
        if ($company->isVatPayer() || $company->vatSettingsIsEditable()) {
            return false;
        }

        $gross_counted = $request->input('default_invoice_gross_counted');
        if ($gross_counted && $gross_counted == NoVat::COUNT_TYPE) {
            return false;
        }

        return true;
    }
}
