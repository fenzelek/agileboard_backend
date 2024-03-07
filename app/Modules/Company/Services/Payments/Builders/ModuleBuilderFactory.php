<?php

namespace App\Modules\Company\Services\Payments\Builders;

use App\Models\Db\Module;

class ModuleBuilderFactory
{
    public function create($module_indicator, $added_params = [])
    {
        if ($module_indicator instanceof Module) {
            return new ModuleBuilder($module_indicator, $added_params);
        }

        $module = Module::whereHas('mods.modPrices', function ($q) use ($module_indicator) {
            $q->where('id', $module_indicator);
        })
            ->with(['mods' => function ($q) use ($module_indicator) {
                $q->whereHas('modPrices', function ($q) use ($module_indicator) {
                    $q->where('id', $module_indicator);
                });
                $q->with([
                        'modPrices' => function ($q) use ($module_indicator) {
                            $q->where('id', $module_indicator);
                        },
                    ]);
            }])
            ->first();

        return new ModuleRebuilder($module, $added_params);
    }
}
