<?php

namespace App\Modules\SaleInvoice\Services\Jpk\Elements;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;

class Root
{
    /**
     * Create root element.
     *
     * @return Element
     */
    public function create()
    {
        $element = new Element('tns:JPK');

        foreach ($this->attributes() as $name => $value) {
            $element->addAttribute(new Attribute($name, $value));
        }

        return $element;
    }

    /**
     * Get attributes that should be set.
     *
     * @return array
     */
    protected function attributes()
    {
        return [
            'xsi:schemaLocation' => 'http://jpk.mf.gov.pl/wzor/2016/03/09/03095/ http://www.mf.gov.pl/documents/764034/5134536/Schemat_JPK_FA(1)_v1-0.xsd',
            'xmlns:tns' => 'http://jpk.mf.gov.pl/wzor/2016/03/09/03095/',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xmlns:etd' => 'http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/',
            'xmlns:kck' => 'http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2013/05/23/eD/KodyCECHKRAJOW/',
            'xmlns:xsd' => 'http://www.w3.org/2001/XMLSchema',
            'xmlns:msxsl' => 'urn:schemas-microsoft-com:xslt',
            'xmlns:usr' => 'urn:the-xml-files:xslt',
        ];
    }
}
