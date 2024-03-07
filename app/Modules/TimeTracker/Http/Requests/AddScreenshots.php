<?php

namespace App\Modules\TimeTracker\Http\Requests;

use App\Modules\TimeTracker\Http\Requests\Contracts\IAddScreens;
use App\Rules\ProjectUserValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class AddScreenshots extends FormRequest implements IAddScreens
{
    public function rules(): array
    {
        return [
            'screen' => ['required', 'mimes:jpeg,jpg,png'],
            'screen_id' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'int', 'gt:0',
                new ProjectUserValidation($this->user()), ],
        ];
    }

    public function getScreenshot(): UploadedFile
    {
        return $this->screen;
    }

    public function getNameScreen(): string
    {
        return $this->input('screen_id');
    }

    public function getProjectId(): int
    {
        return $this->input('project_id');
    }
}
