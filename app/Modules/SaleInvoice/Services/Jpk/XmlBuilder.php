<?php

namespace App\Modules\SaleInvoice\Services\Jpk;

use App\Models\Other\SaleInvoice\Jpk\Attribute;
use App\Models\Other\SaleInvoice\Jpk\Element;
use DOMDocument;
use DOMElement;

class XmlBuilder
{
    /**
     * @var DOMDocument
     */
    protected $dom;

    /**
     * Create XML file from given Element structure.
     *
     * @param Element $element
     *
     * @return string
     */
    public function create(Element $element)
    {
        $this->createRootElement();

        $this->createElement($element, $this->dom);

        return $this->dom->saveXML();
    }

    /**
     * Create root element (DOMDocument).
     */
    protected function createRootElement()
    {
        $this->dom = new DOMDocument('1.0', 'utf-8');
    }

    /**
     * Create DOM element from given Element and add it to given parent.
     *
     * @param Element $element
     * @param DOMDocument|DOMElement $parent
     */
    protected function createElement(Element $element, $parent)
    {
        $dom_element = $this->dom->createElement($element->getName(), $element->getValue());

        /** @var Attribute $attribute */
        foreach ($element->getAttributes() as $attribute) {
            $dom_element->setAttribute($attribute->getName(), $attribute->getValue());
        }

        $parent->appendChild($dom_element);

        foreach ($element->getChildren() as $child) {
            $this->createElement($child, $dom_element);
        }
    }
}
