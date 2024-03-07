<?php

namespace App\Models\Other\SaleInvoice\Jpk;

class Element
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed|null
     */
    protected $value;

    /**
     * @var array
     */
    protected $attributes = [];
    /**
     * @var array
     */
    protected $children = [];

    /**
     * Element constructor.
     *
     * @param string $name
     * @param mixed|null $value
     */
    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Add new attribute.
     *
     * @param Attribute $attribute
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
     * Add new child.
     *
     * @param Element $element
     */
    public function addChild(self $element)
    {
        $this->children[] = $element;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get value.
     *
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get children.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get array representation of object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'attributes' => $this->attributesToArray(),
            'children' => $this->childrenToArray(),
        ];
    }

    /**
     * Get array of attributes.
     *
     * @return array
     */
    protected function attributesToArray()
    {
        return array_map(function ($attribute) {
            return $attribute->toArray();
        }, $this->getAttributes());
    }

    /**
     * Get array of children.
     *
     * @return array
     */
    protected function childrenToArray()
    {
        return array_map(function ($child) {
            return $child->toArray();
        }, $this->getChildren());
    }
}
