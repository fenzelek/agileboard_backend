<?php

namespace App\Modules\Notification\Models\Dto;

class SourceProperty
{
    private string $type;

    private string $id;

    public function __construct(string $type, int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
