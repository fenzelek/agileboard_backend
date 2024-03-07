<?php

namespace App\Modules\SaleInvoice\Services;

use App\Models\Db\Invoice as ModelInvoice;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Models\Other\ContractorAddressType;
use App\Models\Db\ContractorAddress;
use Illuminate\Database\Eloquent\Model;

class DeliveryAddress
{
    /**
     * @var ContractorAddress
     */
    protected $contractor_address;

    /**
     * DeliveryAddress constructor.
     * @param ContractorAddress $contractor_address
     */
    public function __construct(ContractorAddress $contractor_address)
    {
        $this->contractor_address = $contractor_address;
    }

    /**
     * Add delivery address for given invoice.
     *
     * @param ModelInvoice $invoice
     * @param $contractor_address_id
     * @param $default_delivery
     * @return InvoiceDeliveryAddress|Model
     */
    public function addDeliveryAddress(ModelInvoice $invoice, $contractor_address_id, $default_delivery)
    {
        $address = $this->contractor_address->find($contractor_address_id);
        $invoice->delivery_address_id = $address->id;
        $invoice->default_delivery = $default_delivery;
        $invoice->save();

        return $invoice->invoiceDeliveryAddress()->create(
            array_only($address->toArray(), [
                'street',
                'number',
                'zip_code',
                'city',
                'country',
            ]) +
            [
                'receiver_id' => $address->contractor_id,
                'receiver_name' => $address->contractor->name,
            ]
        );
    }

    /**
     * Update Delivery address or remove only if module was disabled.
     *
     * @param ModelInvoice $invoice
     * @param User $user
     * @param $contractor_address_id
     * @param $default_delivery
     */
    public function updateDeliveryAddress(ModelInvoice $invoice, User $user, $contractor_address_id, $default_delivery)
    {
        if ($user->selectedCompany()
            ->appSettings(ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED)
        ) {
            $invoice->delivery_address_id = null;
            $invoice->invoiceDeliveryAddress()->delete();
            $this->addDeliveryAddress($invoice, $contractor_address_id, $default_delivery);
        }
    }

    /**
     * Validate delivery address of contractor belongs to selected company.
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     * 
     * @return bool
     */
    public function validateDeliveryAddress($attribute, $value, $parameters)
    {
        return $this->contractor_address->where('id', $value)
                ->where('type', ContractorAddressType::DELIVERY)
                ->whereHas('contractor', function ($query) use ($parameters) {
                    $query->where('company_id', $parameters[0]);
                })->count() > 0;
    }
}
