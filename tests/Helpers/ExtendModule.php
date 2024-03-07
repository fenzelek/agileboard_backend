<?php

namespace Tests\Helpers;

use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;

trait ExtendModule
{
    protected function createTestExtendModule()
    {
        $module = Module::create([
            'name' => 'test',
            'slug' => 'test.extend.module',
            'description' => 'test',
            'visible' => 1,
            'available' => 1,
        ]);

        $mods = [
            ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => '']),
            ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 'test1']),
            ModuleMod::create(['module_id' => $module->id, 'test' => 0, 'value' => 'test2']),
        ];

        ModPrice::create(['package_id' => null, 'module_mod_id' => $mods[0]->id, 'default' => '1', 'price' => 0, 'currency' => 'PLN']);
        ModPrice::create(['package_id' => null, 'module_mod_id' => $mods[1]->id, 'days' => 30, 'default' => '0', 'price' => 122, 'currency' => 'PLN']);
        ModPrice::create(['package_id' => null, 'module_mod_id' => $mods[2]->id, 'days' => 30, 'default' => '0', 'price' => 122, 'currency' => 'PLN']);
        ModPrice::create(['package_id' => null, 'module_mod_id' => $mods[1]->id, 'days' => 365, 'default' => '0', 'price' => 122, 'currency' => 'PLN']);
        ModPrice::create(['package_id' => null, 'module_mod_id' => $mods[2]->id, 'days' => 365, 'default' => '0', 'price' => 122, 'currency' => 'PLN']);
    }
}
