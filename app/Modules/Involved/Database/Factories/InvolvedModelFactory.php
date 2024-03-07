<?php

declare(strict_types=1);

use App\Models\Db\Involved;
use App\Models\Db\User;
use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

/**
 * @var $factory
 */
$factory->define(
    Involved::class,
    function (Faker\Generator $faker) {
        return [
            'user_id' => factory(User::class)->create(),
            'project_id' => factory(Project::class)->create(),
            'company_id' => factory(Company::class)->create(),
            'source_type' => (new Ticket())->getMorphClass(),
            'source_id' => factory(Ticket::class)->create(),
        ];
    }
);
