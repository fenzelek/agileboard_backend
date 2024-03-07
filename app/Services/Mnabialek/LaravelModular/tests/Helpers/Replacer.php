<?php

namespace Tests\Helpers;

use Illuminate\Console\Command;
use App\Services\Mnabialek\LaravelModular\Models\Module;
use App\Services\Mnabialek\LaravelModular\Traits\Replacer as ReplacerTrait;

class Replacer extends Command
{
    use ReplacerTrait;

    public function runReplace($string, Module $module, array $replacements)
    {
        return $this->replace($string, $module, $replacements);
    }
}
