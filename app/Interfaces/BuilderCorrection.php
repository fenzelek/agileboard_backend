<?php

namespace App\Interfaces;

interface BuilderCorrection extends BuilderCreateInvoice
{
    public function setParent();

    public function setDocument();
}
