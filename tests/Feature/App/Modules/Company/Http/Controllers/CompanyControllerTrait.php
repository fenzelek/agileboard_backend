<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

trait CompanyControllerTrait
{
    private function getGusData($vatin)
    {
        return [
            'name' => 'GŁÓWNY URZĄD STATYSTYCZNY',
            'vatin' => $vatin,
            'regon' => '00033150100000',
            'main_address_number' => 208,
            'main_address_street' => 'Aleja Niepodległości',
            'main_address_zip_code' => '00-925',
            'main_address_city' => 'Warszawa',
            'main_address_country' => 'POLSKA',
            'phone' => '6083000',
            'email' => 'dgsek@stat.gov.pl',
            'website' => 'www.stat.gov.pl',
        ];
    }

    private function getGusDataEconomic($vatin)
    {
        return [
            'name' => 'AND MDX MAGDALENA ŚLEBIODA',
            'vatin' => $vatin,
            'regon' => '302040833',
            'main_address_number' => 3,
            'main_address_street' => 'ul. Skryta',
            'main_address_zip_code' => '64-930',
            'main_address_city' => 'Szydłowo',
            'main_address_country' => 'POLSKA',
            'phone' => '',
            'email' => 'magdalena.slebioda@wp.pl',
            'website' => '',
        ];
    }
}
