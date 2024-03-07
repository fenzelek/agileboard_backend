<?php

use App\Models\Db\CountryVatinPrefix;
use Illuminate\Database\Migrations\Migration;

class FillCountryVatinPrefixTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            foreach ($this->countryVatinPrefixData() as $prefix) {
                CountryVatinPrefix::create($prefix);
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        DB::table('country_vatin_prefixes')->truncate();
    }

    protected function countryVatinPrefixData()
    {
        return [
            [
                'name' => 'Afganistan',
                'key' => 'AF',
            ],[
                'name' => 'Albania',
                'key' => 'AL',
            ],[
                'name' => 'Algieria',
                'key' => 'DZ',
            ],[
                'name' => 'Andora',
                'key' => 'AD',
            ],[
                'name' => 'Angola',
                'key' => 'AO',
            ],[
                'name' => 'Anguilla',
                'key' => 'AI',
            ],[
                'name' => 'Antarktyda',
                'key' => 'AQ',
            ],[
                'name' => 'Antigua i Barbuda',
                'key' => 'AG',
            ],[
                'name' => 'Arabia Saudyjska',
                'key' => 'SA',
            ],[
                'name' => 'Argentyna',
                'key' => 'AR',
            ],[
                'name' => 'Armenia',
                'key' => 'AM',
            ],[
                'name' => 'Aruba',
                'key' => 'AW',
            ],[
                'name' => 'Australia',
                'key' => 'AU',
            ],[
                'name' => 'Austria',
                'key' => 'AT',
            ],[
                'name' => 'Azerbejdżan',
                'key' => 'AZ',
            ],[
                'name' => 'Bahamy',
                'key' => 'BS',
            ],[
                'name' => 'Bahrajn',
                'key' => 'BH',
            ],[
                'name' => 'Bangladesz',
                'key' => 'BD',
            ],[
                'name' => 'Barbados',
                'key' => 'BB',
            ],[
                'name' => 'Belgia',
                'key' => 'BE',
            ],[
                'name' => 'Belize',
                'key' => 'BZ',
            ],[
                'name' => 'Benin',
                'key' => 'BJ',
            ],[
                'name' => 'Bermudy',
                'key' => 'BM',
            ],[
                'name' => 'Bhutan',
                'key' => 'BT',
            ],[
                'name' => 'Białoruś',
                'key' => 'BY',
            ],[
                'name' => 'Boliwia',
                'key' => 'BO',
            ],[
                'name' => 'Bonaire, Sint Eustatius i Saba',
                'key' => 'BQ',
            ],[
                'name' => 'Bośnia i Hercegowina',
                'key' => 'BA',
            ],[
                'name' => 'Botswana',
                'key' => 'BW',
            ],[
                'name' => 'Brazylia',
                'key' => 'BR',
            ],[
                'name' => 'Brunei Darussalam',
                'key' => 'BN',
            ],[
                'name' => 'Bułgaria',
                'key' => 'BG',
            ],[
                'name' => 'Burkina Faso',
                'key' => 'BF',
            ],[
                'name' => 'Burundi',
                'key' => 'BI',
            ],[
                'name' => 'Ceuta',
                'key' => 'XC',
            ],[
                'name' => 'Chile',
                'key' => 'CL',
            ],[
                'name' => 'Chiny',
                'key' => 'CN',
            ],[
                'name' => 'Curaçao',
                'key' => 'CW',
            ],[
                'name' => 'Chorwacja',
                'key' => 'HR',
            ],[
                'name' => 'Cypr',
                'key' => 'CY',
            ],[
                'name' => 'Czad',
                'key' => 'TD',
            ],[
                'name' => 'Czarnogóra',
                'key' => 'ME',
            ],[
                'name' => 'Dania',
                'key' => 'DK',
            ],[
                'name' => 'Dominika',
                'key' => 'DM',
            ],[
                'name' => 'Dominikana',
                'key' => 'DO',
            ],[
                'name' => 'Dżibuti',
                'key' => 'DJ',
            ],[
                'name' => 'Egipt',
                'key' => 'EG',
            ],[
                'name' => 'Ekwador',
                'key' => 'EC',
            ],[
                'name' => 'Erytrea',
                'key' => 'ER',
            ],[
                'name' => 'Estonia',
                'key' => 'EE',
            ],[
                'name' => 'Etiopia',
                'key' => 'ET',
            ],[
                'name' => 'Falklandy',
                'key' => 'FK',
            ],[
                'name' => 'Fidżi Republika',
                'key' => 'FJ',
            ],[
                'name' => 'Filipiny',
                'key' => 'PH',
            ],[
                'name' => 'Finlandia',
                'key' => 'FI',
            ],[
                'name' => 'Francuskie Terytorium Południowe',
                'key' => 'TF',
            ],[
                'name' => 'Francja',
                'key' => 'FR',
            ],[
                'name' => 'Gabon',
                'key' => 'GA',
            ],[
                'name' => 'Gambia',
                'key' => 'GM',
            ],[
                'name' => 'Ghana',
                'key' => 'GH',
            ],[
                'name' => 'Gibraltar',
                'key' => 'GI',
            ],[
                'name' => 'Grecja',
                'key' => 'GR',
            ],[
                'name' => 'Grenada',
                'key' => 'GD',
            ],[
                'name' => 'Grenlandia',
                'key' => 'GL',
            ],[
                'name' => 'Gruzja',
                'key' => 'GE',
            ],[
                'name' => 'Guam',
                'key' => 'GU',
            ],[
                'name' => 'Gujana',
                'key' => 'GY',
            ],[
                'name' => 'Gwatemala',
                'key' => 'GT',
            ],[
                'name' => 'Gwinea',
                'key' => 'GN',
            ],[
                'name' => 'Gwinea Równikowa',
                'key' => 'GQ',
            ],[
                'name' => 'Gwinea-Bissau',
                'key' => 'GW',
            ],[
                'name' => 'Haiti',
                'key' => 'HT',
            ],[
                'name' => 'Hiszpania',
                'key' => 'ES',
            ],[
                'name' => 'Honduras',
                'key' => 'HN',
            ],[
                'name' => 'Hongkong',
                'key' => 'HK',
            ],[
                'name' => 'Indie',
                'key' => 'IN',
            ],[
                'name' => 'Indonezja',
                'key' => 'ID',
            ],[
                'name' => 'Irak',
                'key' => 'IQ',
            ],[
                'name' => 'Iran',
                'key' => 'IR',
            ],[
                'name' => 'Irlandia',
                'key' => 'IE',
            ],[
                'name' => 'Islandia',
                'key' => 'IS',
            ],[
                'name' => 'Izrael',
                'key' => 'IL',
            ],[
                'name' => 'Jamajka',
                'key' => 'JM',
            ],[
                'name' => 'Japonia',
                'key' => 'JP',
            ],[
                'name' => 'Jemen',
                'key' => 'YE',
            ],[
                'name' => 'Jordania',
                'key' => 'JO',
            ],[
                'name' => 'Kajmany',
                'key' => 'KY',
            ],[
                'name' => 'Kambodża',
                'key' => 'KH',
            ],[
                'name' => 'Kamerun',
                'key' => 'CM',
            ],[
                'name' => 'Kanada',
                'key' => 'CA',
            ],[
                'name' => 'Katar',
                'key' => 'QA',
            ],[
                'name' => 'Kazachstan',
                'key' => 'KZ',
            ],[
                'name' => 'Kenia',
                'key' => 'KE',
            ],[
                'name' => 'Kirgistan',
                'key' => 'KG',
            ],[
                'name' => 'Kiribati',
                'key' => 'KI',
            ],[
                'name' => 'Kolumbia',
                'key' => 'CO',
            ],[
                'name' => 'Komory',
                'key' => 'KM',
            ],[
                'name' => 'Kongo',
                'key' => 'CG',
            ],[
                'name' => 'Kongo, Republika Demokratyczna',
                'key' => 'CD',
            ],[
                'name' => 'Koreańska Republika Ludowo-Demokratyczna',
                'key' => 'KP',
            ],[
                'name' => 'Kosowo',
                'key' => 'XK',
            ],[
                'name' => 'Kostaryka',
                'key' => 'CR',
            ],[
                'name' => 'Kuba',
                'key' => 'CU',
            ],[
                'name' => 'Kuwejt',
                'key' => 'KW',
            ],[
                'name' => 'Laos',
                'key' => 'LA',
            ],[
                'name' => 'Lesotho',
                'key' => 'LS',
            ],[
                'name' => 'Liban',
                'key' => 'LB',
            ],[
                'name' => 'Liberia',
                'key' => 'LR',
            ],[
                'name' => 'Libia',
                'key' => 'LY',
            ],[
                'name' => 'Liechtenstein',
                'key' => 'LI',
            ],[
                'name' => 'Litwa',
                'key' => 'LT',
            ],[
                'name' => 'Luksemburg',
                'key' => 'LU',
            ],[
                'name' => 'Łotwa',
                'key' => 'LV',
            ],[
                'name' => 'Macedonia',
                'key' => 'MK',
            ],[
                'name' => 'Madagaskar',
                'key' => 'MG',
            ],[
                'name' => 'Majotta',
                'key' => 'YT',
            ],[
                'name' => 'Makau',
                'key' => 'MO',
            ],[
                'name' => 'Malawi',
                'key' => 'MW',
            ],[
                'name' => 'Malediwy',
                'key' => 'MV',
            ],[
                'name' => 'Malezja',
                'key' => 'MY',
            ],[
                'name' => 'Mali',
                'key' => 'ML',
            ],[
                'name' => 'Malta',
                'key' => 'MT',
            ],[
                'name' => 'Mariany Północne',
                'key' => 'MP',
            ],[
                'name' => 'Maroko',
                'key' => 'MA',
            ],[
                'name' => 'Mauretania',
                'key' => 'MR',
            ],[
                'name' => 'Mauritius',
                'key' => 'MU',
            ],[
                'name' => 'Meksyk',
                'key' => 'MX',
            ],[
                'name' => 'Melilla',
                'key' => 'XL',
            ],[
                'name' => 'Mikronezja',
                'key' => 'FM',
            ],[
                'name' => 'Mołdowa',
                'key' => 'MD',
            ],[
                'name' => 'Mongolia',
                'key' => 'MN',
            ],[
                'name' => 'Montserrat',
                'key' => 'MS',
            ],[
                'name' => 'Mozambik',
                'key' => 'MZ',
            ],[
                'name' => 'Myanmar (Burma)',
                'key' => 'MM',
            ],[
                'name' => 'Namibia',
                'key' => 'NA',
            ],[
                'name' => 'Nauru',
                'key' => 'NR',
            ],[
                'name' => 'Nepal',
                'key' => 'NP',
            ],[
                'name' => 'Niderlandy',
                'key' => 'NL',
            ],[
                'name' => 'Niemcy',
                'key' => 'DE',
            ],[
                'name' => 'Niger',
                'key' => 'NE',
            ],[
                'name' => 'Nigeria',
                'key' => 'NG',
            ],[
                'name' => 'Nikaragua',
                'key' => 'NI',
            ],[
                'name' => 'Niue',
                'key' => 'NU',
            ],[
                'name' => 'Norfolk',
                'key' => 'NF',
            ],[
                'name' => 'Norwegia',
                'key' => 'NO',
            ],[
                'name' => 'Nowa Kaledonia',
                'key' => 'NC',
            ],[
                'name' => 'Nowa Zelandia',
                'key' => 'NZ',
            ],[
                'name' => 'Okupowane Terytorium Palestyny',
                'key' => 'PS',
            ],[
                'name' => 'Oman',
                'key' => 'OM',
            ],[
                'name' => 'Pakistan',
                'key' => 'PK',
            ],[
                'name' => 'Palau',
                'key' => 'PW',
            ],[
                'name' => 'Panama',
                'key' => 'PA',
            ],[
                'name' => 'Papua Nowa Gwinea',
                'key' => 'PG',
            ],[
                'name' => 'Paragwaj',
                'key' => 'PY',
            ],[
                'name' => 'Peru',
                'key' => 'PE',
            ],[
                'name' => 'Pitcairn',
                'key' => 'PN',
            ],[
                'name' => 'Polinezja Francuska',
                'key' => 'PF',
            ],[
                'name' => 'Polska',
                'key' => 'PL',
            ],[
                'name' => 'Południowa Georgia i Południowe Wyspy Sandwich',
                'key' => 'GS',
            ],[
                'name' => 'Portugalia',
                'key' => 'PT',
            ],[
                'name' => 'Republika Czeska',
                'key' => 'CZ',
            ],[
                'name' => 'Republika Korei',
                'key' => 'KR',
            ],[
                'name' => 'Rep.Połud.Afryki',
                'key' => 'ZA',
            ],[
                'name' => 'Rep.Środkowoafryańska',
                'key' => 'CF',
            ],[
                'name' => 'Rosja',
                'key' => 'RU',
            ],[
                'name' => 'Rwanda',
                'key' => 'RW',
            ],[
                'name' => 'Sahara Zachodnia',
                'key' => 'EH',
            ],[
                'name' => 'Saint Barthelemy',
                'key' => 'BL',
            ],[
                'name' => 'Rumunia',
                'key' => 'RO',
            ],[
                'name' => 'Salwador',
                'key' => 'SV',
            ],[
                'name' => 'Samoa',
                'key' => 'WS',
            ],[
                'name' => 'Samoa Amerykańskie',
                'key' => 'AS',
            ],[
                'name' => 'San Marino',
                'key' => 'SM',
            ],[
                'name' => 'Senegal',
                'key' => 'SN',
            ],[
                'name' => 'Serbia',
                'key' => 'XS',
            ],[
                'name' => 'Seszele',
                'key' => 'SC',
            ],[
                'name' => 'Sierra Leone',
                'key' => 'SL',
            ],[
                'name' => 'Singapur',
                'key' => 'SG',
            ],[
                'name' => 'Suazi',
                'key' => 'SZ',
            ],[
                'name' => 'Słowacja',
                'key' => 'SK',
            ],[
                'name' => 'Słowenia',
                'key' => 'SI',
            ],[
                'name' => 'Somalia',
                'key' => 'SO',
            ],[
                'name' => 'Sri Lanka',
                'key' => 'LK',
            ],[
                'name' => 'St. Pierre i Miquelon',
                'key' => 'PM',
            ],[
                'name' => 'St.Kitts i Nevis',
                'key' => 'KN',
            ],[
                'name' => 'St.Lucia',
                'key' => 'LC',
            ],[
                'name' => 'St.Vincent i Grenadyny',
                'key' => 'VC',
            ],[
                'name' => 'Stany Zjedn. Ameryki',
                'key' => 'US',
            ],[
                'name' => 'Sudan',
                'key' => 'SD',
            ],[
                'name' => 'Sudan Południowy',
                'key' => 'SS',
            ],[
                'name' => 'Surinam',
                'key' => 'SR',
            ],[
                'name' => 'Syria',
                'key' => 'SY',
            ],[
                'name' => 'Szwajcaria',
                'key' => 'CH',
            ],[
                'name' => 'Szwecja',
                'key' => 'SE',
            ],[
                'name' => 'Święta Helena',
                'key' => 'SH',
            ],[
                'name' => 'Tadżykistan',
                'key' => 'TJ',
            ],[
                'name' => 'Tajlandia',
                'key' => 'TH',
            ],[
                'name' => 'Tajwan',
                'key' => 'TW',
            ],[
                'name' => 'Tanzania',
                'key' => 'TZ',
            ],[
                'name' => 'Togo',
                'key' => 'TG',
            ],[
                'name' => 'Tokelau',
                'key' => 'TK',
            ],[
                'name' => 'Tonga',
                'key' => 'TO',
            ],[
                'name' => 'Trynidad i Tobago',
                'key' => 'TT',
            ],[
                'name' => 'Tunezja',
                'key' => 'TN',
            ],[
                'name' => 'Turcja',
                'key' => 'TR',
            ],[
                'name' => 'Turkmenistan',
                'key' => 'TM',
            ],[
                'name' => 'Wyspy Turks i Caicos',
                'key' => 'TC',
            ],[
                'name' => 'Tuvalu',
                'key' => 'TV',
            ],[
                'name' => 'Uganda',
                'key' => 'UG',
            ],[
                'name' => 'Ukraina',
                'key' => 'UA',
            ],[
                'name' => 'Urugwaj',
                'key' => 'UY',
            ],[
                'name' => 'Uzbekistan',
                'key' => 'UZ',
            ],[
                'name' => 'Vanuatu',
                'key' => 'VU',
            ],[
                'name' => 'Wallis i Futuna',
                'key' => 'WF',
            ],[
                'name' => 'Watykan',
                'key' => 'VA',
            ],[
                'name' => 'Wenezuela',
                'key' => 'VE',
            ],[
                'name' => 'Węgry',
                'key' => 'HU',
            ],[
                'name' => 'Wielka Brytania',
                'key' => 'GB',
            ],[
                'name' => 'Wietnam',
                'key' => 'VN',
            ],[
                'name' => 'Włochy',
                'key' => 'IT',
            ],[
                'name' => 'Wschodni Timor',
                'key' => 'TL',
            ],[
                'name' => 'Wyb.Kości Słoniowej',
                'key' => 'CI',
            ],[
                'name' => 'Wyspa Bouveta',
                'key' => 'BV',
            ],[
                'name' => 'Wyspa Bożego Narodzenia',
                'key' => 'CX',
            ],[
                'name' => 'Wyspy Cooka',
                'key' => 'CK',
            ],[
                'name' => 'Wyspy Dziewicze-USA',
                'key' => 'VI',
            ],[
                'name' => 'Wyspy Dziewicze-W.B',
                'key' => 'VG',
            ],[
                'name' => 'Wyspy Heard i McDonald',
                'key' => 'HM',
            ],[
                'name' => 'Wyspy Kokosowe (Keelinga)',
                'key' => 'CC',
            ],[
                'name' => 'Wyspy Owcze',
                'key' => 'FO',
            ],[
                'name' => 'Wyspy Marshalla',
                'key' => ' MH',
            ],[
                'name' => 'Wyspy Salomona',
                'key' => 'SB',
            ],[
                'name' => 'Wyspy Św.Tomasza i Książęca',
                'key' => 'ST',
            ],[
                'name' => 'Zambia',
                'key' => 'ZM',
            ],[
                'name' => 'Zielony Przylądek',
                'key' => 'CV',
            ],[
                'name' => 'Zimbabwe',
                'key' => 'ZW',
            ],[
                'name' => 'Zjedn.Emiraty Arabskie',
                'key' => 'AE',
            ],
        ];
    }
}
