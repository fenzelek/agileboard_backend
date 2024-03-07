<?php

namespace App\Modules\TimeTracker\Http\Requests\Contracts;

use Illuminate\Http\UploadedFile;

interface IAddScreens
{
    public function getScreenshot(): UploadedFile;

    public function getNameScreen(): string;

    public function getProjectId(): int;
}
