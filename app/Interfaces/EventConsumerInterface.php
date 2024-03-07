<?php

namespace App\Interfaces;

use App\Events\EventInterface;

interface EventConsumerInterface
{
    public function proceed(EventInterface $event);
}
