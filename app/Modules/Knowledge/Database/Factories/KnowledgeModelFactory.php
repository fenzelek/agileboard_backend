<?php

use App\Models\Other\KnowledgePageCommentType;

$factory->define(
    \App\Models\Db\Knowledge\KnowledgePage::class,
    function (Faker\Generator $faker) {
        return [
            'project_id' => function ($faker) {
                return factory(\App\Models\Db\Project::class)->create()->id;
            },
            'creator_id' => function ($faker) {
                return factory(\App\Models\Db\User::class)->create()->id;
            },
            'knowledge_directory_id' => null,
            'name' => $faker->unique()->name,
            'content' => $faker->text(1000),
        ];
    }
);

$factory->define(
    \App\Models\Db\Knowledge\KnowledgeDirectory::class,
    function (Faker\Generator $faker) {
        return [
            'project_id' => function ($faker) {
                return factory(\App\Models\Db\Project::class)->create()->id;
            },
            'creator_id' => function ($faker) {
                return factory(\App\Models\Db\User::class)->create()->id;
            },
            'name' => $faker->unique()->name,
        ];
    }
);

$factory->define(
    \App\Models\Db\KnowledgePageComment::class,
    function (Faker\Generator $faker) {
        return [
            'knowledge_page_id' => $faker->numberBetween(),
            'user_id' => $faker->numberBetween(),
            'text' => $faker->text,
            'type' => array_rand(KnowledgePageCommentType::all()),
            'ref' => '#' . $faker->text,
        ];
    }
);
