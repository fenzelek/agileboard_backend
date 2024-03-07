<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(\App\Models\Db\Project::class, function (Faker\Generator $faker) {
    $projectName = $faker->unique()->company;

    return [
        'name' => $projectName,
        'short_name' => mb_substr($projectName, 0, 15),
        'closed' => $faker->boolean(),
        'deleted' => $faker->boolean(),
    ];
});

Modular::loadFactories($factory);
