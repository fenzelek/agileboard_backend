<?php

namespace App\Modules\Notification\Services;

class Mailable extends \Illuminate\Mail\Mailable
{
    public function build()
    {
        return $this;
    }
}
