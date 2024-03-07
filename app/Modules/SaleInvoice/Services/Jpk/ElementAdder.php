<?php

namespace App\Modules\SaleInvoice\Services\Jpk;

use App\Models\Other\SaleInvoice\Jpk\Element;

trait ElementAdder
{
    /**
     * JPK elements.
     *
     * @var array
     */
    protected $jpk_elements = [];

    /**
     * @var Element|null
     */
    protected $parent;

    /**
     * Set parent element.
     *
     * @param Element $element
     */
    protected function setParentElement(Element $element)
    {
        $this->parent = $element;
    }

    /**
     * Add child element.
     *
     * @param Element $element
     */
    protected function addChildElement(Element $element)
    {
        $this->parent->addChild($element);
    }

    /**
     * Get parent element.
     *
     * @return Element|null
     */
    protected function getParentElement()
    {
        return $this->parent;
    }

    /**
     * Add element to JPK elements.
     *
     * @param Element $element
     */
    protected function addElement(Element $element)
    {
        $this->jpk_elements[] = $element;
    }

    /**
     * Get JPK elements.
     *
     * @return array
     */
    protected function getElements()
    {
        return $this->jpk_elements;
    }

    /**
     * Clear JPK elements.
     */
    protected function clearElements()
    {
        $this->jpk_elements = [];
    }

    /**
     * Add multiple JPK elements.
     *
     * @param array $elements
     */
    protected function addElements(array $elements)
    {
        foreach ($elements as $element) {
            $this->addElement($element);
        }
    }
}
