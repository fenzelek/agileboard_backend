<?php

namespace Tests\Unit\App\Models\Db\CountryVatinPrefix;

use App\Models\Db\CountryVatinPrefix;
use Tests\TestCase;

class InEuropeUnionTest extends TestCase
{
    /** @test */
    public function it_verifies_whether_all_countries_are_covered_in_tests()
    {
        $db_count = CountryVatinPrefix::count();
        $this->assertSame(count($this->getEuropeUnionKeys()) +
            count($this->getOutsideEuropeUnionKeys()), $db_count);
    }

    /** @test */
    public function it_returns_true_for_all_countries_inside_europe_union()
    {
        $country_vat_in_prefix = new CountryVatinPrefix();

        foreach ($this->getEuropeUnionKeys() as $key) {
            $country_vat_in_prefix->key = $key;
            $this->assertTrue(
                $country_vat_in_prefix->inEuropeUnion(),
                'Country with code ' . $key . ' is in Europe Union'
            );
        }
    }

    /** @test */
    public function it_returns_false_for_all_countries_outside_europe_union()
    {
        $country_vat_in_prefix = new CountryVatinPrefix();

        foreach ($this->getOutsideEuropeUnionKeys() as $key) {
            $country_vat_in_prefix->key = $key;
            $this->assertFalse(
                $country_vat_in_prefix->inEuropeUnion(),
                'Country with code ' . $key . ' is NOT in Europe Union'
            );
        }
    }

    protected function getEuropeUnionKeys()
    {
        return [
            'AT',
            'BE',
            'BG',
            'HR',
            'CY',
            'CZ',
            'DK',
            'EE',
            'FI',
            'FR',
            'GR',
            'ES',
            'NL',
            'IE',
            'LT',
            'LU',
            'LV',
            'MT',
            'DE',
            'PL',
            'PT',
            'RO',
            'SK',
            'SI',
            'SE',
            'HU',
            'IT',
            'GB',
        ];
    }

    protected function getOutsideEuropeUnionKeys()
    {
        return [
            'AF',
            'AL',
            'DZ',
            'AD',
            'AO',
            'AI',
            'AQ',
            'AG',
            'SA',
            'AR',
            'AM',
            'AW',
            'AU',
            'AZ',
            'BS',
            'BH',
            'BD',
            'BB',
            'BZ',
            'BJ',
            'BM',
            'BT',
            'BY',
            'BO',
            'BQ',
            'BA',
            'BW',
            'BR',
            'BN',
            'BF',
            'BI',
            'XC',
            'CL',
            'CN',
            'CW',
            'TD',
            'ME',
            'DM',
            'DO',
            'DJ',
            'EG',
            'EC',
            'ER',
            'ET',
            'FK',
            'FJ',
            'PH',
            'TF',
            'GA',
            'GM',
            'GH',
            'GI',
            'GD',
            'GL',
            'GE',
            'GU',
            'GY',
            'GT',
            'GN',
            'GQ',
            'GW',
            'HT',
            'HN',
            'HK',
            'IN',
            'ID',
            'IQ',
            'IR',
            'IS',
            'IL',
            'JM',
            'JP',
            'YE',
            'JO',
            'KY',
            'KH',
            'CM',
            'CA',
            'QA',
            'KZ',
            'KE',
            'KG',
            'KI',
            'CO',
            'KM',
            'CG',
            'CD',
            'KP',
            'XK',
            'CR',
            'CU',
            'KW',
            'LA',
            'LS',
            'LB',
            'LR',
            'LY',
            'LI',
            'MK',
            'MG',
            'YT',
            'MO',
            'MW',
            'MV',
            'MY',
            'ML',
            'MP',
            'MA',
            'MR',
            'MU',
            'MX',
            'XL',
            'FM',
            'MD',
            'MN',
            'MS',
            'MZ',
            'MM',
            'NA',
            'NR',
            'NP',
            'NE',
            'NG',
            'NI',
            'NU',
            'NF',
            'NO',
            'NC',
            'NZ',
            'PS',
            'OM',
            'PK',
            'PW',
            'PA',
            'PG',
            'PY',
            'PE',
            'PN',
            'PF',
            'GS',
            'KR',
            'ZA',
            'CF',
            'RU',
            'RW',
            'EH',
            'BL',
            'SV',
            'WS',
            'AS',
            'SM',
            'SN',
            'XS',
            'SC',
            'SL',
            'SG',
            'SZ',
            'SO',
            'LK',
            'PM',
            'KN',
            'LC',
            'VC',
            'US',
            'SD',
            'SS',
            'SR',
            'SY',
            'CH',
            'SH',
            'TJ',
            'TH',
            'TW',
            'TZ',
            'TG',
            'TK',
            'TO',
            'TT',
            'TN',
            'TR',
            'TM',
            'TC',
            'TV',
            'UG',
            'UA',
            'UY',
            'UZ',
            'VU',
            'WF',
            'VA',
            'VE',
            'VN',
            'TL',
            'CI',
            'BV',
            'CX',
            'CK',
            'VI',
            'VG',
            'HM',
            'CC',
            'FO',
            'MH',
            'SB',
            'ST',
            'ZM',
            'CV',
            'ZW',
            'AE',
        ];
    }
}
