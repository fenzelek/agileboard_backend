<?php

$factory->define(
    \App\Models\Db\Project::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->unique()->name,
            'short_name' => $faker->unique()->word,
            'time_tracking_visible_for_clients' => $faker->boolean,
            'language' => 'en',
            'email_notification_enabled' => false,
            'slack_notification_enabled' => false,
            'slack_webhook_url' => $faker->url,
            'slack_channel' => $faker->name,
            'color' => $faker->colorName,
        ];
    }
);

$factory->define(
    \App\Models\Db\File::class,
    function (Faker\Generator $faker) {
        return [
            'id' => $faker->randomNumber(5),
            'user_id' => $faker->randomNumber(4),
            'name' => $faker->name,
            'storage_name' => $faker->unique()->word,
            'description' => $faker->text,
            'project_id' => $faker->randomNumber(4),
        ];
    }
);

$factory->define(
    \App\Models\Db\Story::class,
    function (Faker\Generator $faker) {
        return [
            'id' => $faker->numberBetween(),
            'name' => $faker->name,
            'color' => $faker->hexColor,
            'priority' => $faker->numberBetween(),
            'deleted_at' => null,
        ];
    }
);
