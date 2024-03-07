<?php

namespace App\Http\Resources;

class PaymentIndex extends Payment
{
    /**
     * @inheritdoc
     */
    protected $ignoredRelationships = ['transaction'];
}
