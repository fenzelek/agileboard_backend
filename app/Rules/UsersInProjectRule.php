<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Db\ProjectUser;
use Illuminate\Contracts\Validation\Rule;

class UsersInProjectRule implements Rule
{
    private int $project_id;

    private array $users_not_in_project = [];

    public function __construct(int $project_id)
    {
        $this->project_id = $project_id;
    }

    /**
     * @throws \Exception
     */
    public function passes($attribute, $value): bool
    {
        $users_to_validate = $value;
        if (! is_array($users_to_validate)) {
            throw new \Exception("Validated attribute: {$attribute} must be array");
        }
        if (count($users_to_validate)===0) {
            return true;
        }

        $users_in_project = ProjectUser::query()
            ->where('project_id', $this->project_id)
            ->whereIn('user_id', $value)
            ->pluck('user_id')
            ->toArray();

        $this->users_not_in_project = $this->usersInProject($users_in_project, $users_to_validate);

        return count($this->users_not_in_project) === 0;
    }

    public function message(): string
    {
        return 'This users are not in project: ' . json_encode($this->users_not_in_project);
    }

    private function usersInProject(array $users_in_project, array $users_to_validate): array
    {
        return array_diff($users_to_validate, $users_in_project );
    }
}
