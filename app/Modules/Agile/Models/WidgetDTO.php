<?php
declare(strict_types=1);

namespace App\Modules\Agile\Models;

class WidgetDTO
{
    private string $name;
    private $data;

    /**
     * @param string $name
     * @param $get
     */
    public function __construct(string $name, $data)
    {
        $this->data = $data;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}