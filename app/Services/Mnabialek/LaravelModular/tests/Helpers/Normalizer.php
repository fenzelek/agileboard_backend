<?php

namespace Tests\Helpers;

use App\Services\Mnabialek\LaravelModular\Traits\Normalizer as NormalizerTrait;

class Normalizer
{
    use NormalizerTrait;

    public function runNormalizePath($path)
    {
        return $this->normalizePath($path);
    }
}
