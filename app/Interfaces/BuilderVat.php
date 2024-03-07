<?php

namespace App\Interfaces;

interface BuilderVat extends BuilderCreateInvoice
{
    public function setDocument();
}
