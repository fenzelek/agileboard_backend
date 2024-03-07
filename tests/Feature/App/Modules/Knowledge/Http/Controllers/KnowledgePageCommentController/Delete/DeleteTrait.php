<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController\Delete;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;

trait DeleteTrait
{
    public function authorizedRoleDataProvider(): array
    {
        return [
            RoleType::OWNER => [RoleType::OWNER],
            RoleType::ADMIN => [RoleType::ADMIN],
            RoleType::EMPLOYEE => [RoleType::EMPLOYEE],
            RoleType::DEVELOPER => [RoleType::DEVELOPER],
            RoleType::CLIENT => [RoleType::CLIENT],
        ];
    }

    public function unauthorizedRoleDataProvider(): array
    {
        return [
            RoleType::SYSTEM_USER => [RoleType::SYSTEM_USER],
            RoleType::TAX_OFFICE => [RoleType::TAX_OFFICE],
        ];
    }

    protected function createKnowledgePageInUserProject(User $user, string $role_type, int $company_id): KnowledgePage
    {
        $project = factory(Project::class)->create(['company_id' => $company_id]);
        $project->users()->attach($user->id, ['role_id' => Role::findByName($role_type)->id]);

        return factory(KnowledgePage::class)->create(['project_id' => $project->id]);
    }

    protected function addComment(KnowledgePage $page): KnowledgePageComment
    {
        return factory(KnowledgePageComment::class)->create(['knowledge_page_id' => $page->id]);
    }
}
