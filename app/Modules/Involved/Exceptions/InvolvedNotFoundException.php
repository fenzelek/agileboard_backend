<?php

declare(strict_types=1);

namespace App\Modules\Involved\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvolvedNotFoundException extends ModelNotFoundException
{
    public function __construct(string $source_type, int $source_id)
    {
        $message = "Involved for source {$source_type} and id: {$source_id} not found";
        parent::__construct($message, 0, null);
    }
}
