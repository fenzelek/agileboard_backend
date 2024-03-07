<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers;

use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;

class Address
{
    /**
     * Get company address.
     *
     * @param InvoiceCompany $company
     *
     * @return string
     */
    public function getCompanyAddress(InvoiceCompany $company)
    {
        return $this->getAddress($company);
    }

    /**
     * Get contractor address.
     *
     * @param InvoiceContractor $contractor
     *
     * @return string
     */
    public function getContractorAddress(InvoiceContractor $contractor)
    {
        return $this->getAddress($contractor);
    }

    /**
     * Get address for company/contractor.
     *
     * @param InvoiceContractor|InvoiceCompany $object
     *
     * @return string
     */
    protected function getAddress($object)
    {
        $address = $object->main_address_street . ' ' . $object->main_address_number . ', ';

        if ($object->main_address_zip_code) {
            $address .= $object->main_address_zip_code . ' ';
        }

        $address .= $object->main_address_city;

        if ($object->main_address_country &&
            mb_strtoupper($object->main_address_country) != 'POLSKA') {
            $address .= ', ' . $object->main_address_country;
        }

        return $address;
    }
}
