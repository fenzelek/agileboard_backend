<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;

use App\Models\Db\InvoiceContractor;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use Tests\TestCase;

class GetContractorAddressTest extends TestCase
{
    /** @test */
    public function it_returns_valid_contractor_address()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '54-123';
        $city = 'XC"ere Sample city';

        $invoice_contractor = new InvoiceContractor([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $zip_code . ' ' . $city,
            $address->getContractorAddress($invoice_contractor)
        );
    }

    /** @test */
    public function it_returns_valid_contractor_address_without_zip_code()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '';
        $city = 'XC"ere Sample city';

        $invoice_contractor = new InvoiceContractor([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
            'main_address_country' => 'Polska',
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $city,
            $address->getContractorAddress($invoice_contractor)
        );
    }

    /** @test */
    public function it_returns_valid_contractor_address_for_contractor_outside_poland()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '';
        $city = 'XC"ere Sample city';
        $country = 'Grecja';

        $invoice_contractor = new InvoiceContractor([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
            'main_address_country' => $country,
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $city . ', ' . $country,
            $address->getContractorAddress($invoice_contractor)
        );
    }

    /** @test */
    public function it_returns_valid_contractor_address_for_contractor_outside_poland_with_zipcode()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '312-323';
        $city = 'XC"ere Sample city';
        $country = 'Grecja';

        $invoice_contractor = new InvoiceContractor([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
            'main_address_country' => $country,
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $zip_code . ' ' . $city . ', ' . $country,
            $address->getContractorAddress($invoice_contractor)
        );
    }
}
