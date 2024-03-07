<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;

use App\Models\Db\InvoiceCompany;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Helpers\Address;
use Tests\TestCase;

class GetCompanyAddressTest extends TestCase
{
    /** @test */
    public function it_returns_valid_company_address()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '54-123';
        $city = 'XC"ere Sample city';

        $invoice_company = new InvoiceCompany([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $zip_code . ' ' . $city,
            $address->getCompanyAddress($invoice_company)
        );
    }

    /** @test */
    public function it_returns_valid_company_address_when_in_poland()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '54-123';
        $city = 'XC"ere Sample city';

        $invoice_company = new InvoiceCompany([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
            'main_address_country' => 'PolSKA',
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $zip_code . ' ' . $city,
            $address->getCompanyAddress($invoice_company)
        );
    }

    /** @test */
    public function it_returns_valid_company_address_when_outside_poland()
    {
        $street = 'Sample street" 23';
        $number = 'Sample number';
        $zip_code = '54-123';
        $city = 'XC"ere Sample city';
        $country = 'Grecja';

        $invoice_company = new InvoiceCompany([
            'main_address_street' => $street,
            'main_address_number' => $number,
            'main_address_zip_code' => $zip_code,
            'main_address_city' => $city,
            'main_address_country' => $country,
        ]);

        $address = new Address();

        $this->assertSame(
            $street . ' ' . $number . ', ' . $zip_code . ' ' . $city . ', ' . $country,
            $address->getCompanyAddress($invoice_company)
        );
    }
}
