<?php

$factory->define(
    \App\Models\Db\Integration\IntegrationProvider::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->unique()->text,
            'name' => $faker->text,
            'type' => $faker->text(50),
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\Integration::class,
    function (Faker\Generator $faker) {
        return [
            'company_id' => $faker->randomNumber,
            'integration_provider_id' => $faker->randomNumber,
            'settings' => [
                'app_token' => 'secret app token',
                'auth_token' => 'secret auth token',
                'secret_password' => 'secret password',
            ],
            'info' => [
                'data' => $faker->text(50),
            ],
            'active' => $faker->boolean(),
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\TimeTracking\User::class,
    function (Faker\Generator $faker) {
        return [
            'integration_id' => $faker->unique()->randomNumber,
            'user_id' => $faker->unique()->randomNumber,
            'external_user_id' => $faker->unique()->randomNumber,
            'external_user_email' => $faker->safeEmail,
            'external_user_name' => $faker->firstName . ' ' . $faker->lastName,
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\TimeTracking\Activity::class,
    function (Faker\Generator $faker) {
        return [
            'integration_id' => $faker->unique()->randomNumber,
            'user_id' => $faker->unique()->randomNumber,
            'project_id' => $faker->unique()->randomNumber,
            'ticket_id' => $faker->unique()->randomNumber,
            'locked_user_id' => $faker->unique()->randomNumber,
            'external_activity_id' => $faker->unique()->randomNumber,
            'time_tracking_user_id' => $faker->unique()->randomNumber,
            'time_tracking_project_id' => $faker->unique()->randomNumber,
            'time_tracking_note_id' => $faker->unique()->randomNumber,
            'utc_started_at' => $faker->dateTime->format('Y-m-d H:i:s'),
            'utc_finished_at' => $faker->dateTime->format('Y-m-d H:i:s'),
            'tracked' => $faker->numberBetween(1, 600),
            'activity' => $faker->numberBetween(1, 600),
            'comment' => $faker->text(255),
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\TimeTracking\Note::class,
    function (Faker\Generator $faker) {
        return [
            'integration_id' => $faker->unique()->randomNumber,
            'external_note_id' => $faker->unique()->randomNumber,
            'external_project_id' => $faker->unique()->randomNumber,
            'external_user_id' => $faker->unique()->randomNumber,
            'content' => $faker->text(255),
            'utc_recorded_at' => $faker->dateTime->format('Y-m-d H:i:s'),
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\TimeTracking\Project::class,
    function (Faker\Generator $faker) {
        return [
            'integration_id' => $faker->unique()->randomNumber,
            'project_id' => $faker->randomNumber,
            'external_project_id' => $faker->unique()->randomNumber,
            'external_project_name' => $faker->text(255),
        ];
    }
);

$factory->define(
    \App\Models\Db\Integration\TimeTracking\ManualActivityHistory::class,
    function (Faker\Generator $faker) {
        return [
            'author_id' => $faker->unique()->randomNumber,
            'user_id' => $faker->unique()->randomNumber,
            'project_id' => $faker->unique()->randomNumber,
            'ticket_id' => $faker->unique()->randomNumber,
            'from' => $faker->dateTime->format('Y-m-d H:i:s'),
            'to' => $faker->dateTime->format('Y-m-d H:i:s'),
        ];
    }
);
