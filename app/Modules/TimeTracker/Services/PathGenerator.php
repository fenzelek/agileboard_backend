<?php

namespace App\Modules\TimeTracker\Services;

use App\Models\Db\User;
use App\Modules\TimeTracker\Http\Requests\Contracts\IAddScreens;
use App\Modules\TimeTracker\Models\ScreenPaths;
use App\Modules\TimeTracker\Traits\Helper;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Facades\Storage;

class PathGenerator
{
    use Helper;

    private const THUMB = 'thumb';
    private const URL = 'url';
    private const DIRECTORY_SEPARATOR = '/';
    private const UNDERSCORE = '_';

    private ConfigContract $config;
    private User $user;
    private Storage $storage;

    public function __construct(Guard $guard, ConfigContract $config, Storage $storage)
    {
        $this->user = $guard->user();
        $this->config = $config;
        $this->storage = $storage;
    }

    public function pathBuilder(IAddScreens $screen_files_provider): ScreenPaths
    {
        $screen_paths = new ScreenPaths();
        $screen_paths->setScreenName($screen_files_provider->getNameScreen());
        $screen_paths->setValid(true);

        $screen_paths->setFilePathThumb($this->getFilePath($screen_files_provider, self::THUMB));
        $screen_paths->setStoragePathThumb($this->storage::disk($this->config->get('image.disk'))->path($screen_paths->getFilePathThumb()));

        $screen_paths->setFilePathUrl($this->getFilePath($screen_files_provider, self::URL));
        $screen_paths->setStoragePathUrl($this->storage::disk($this->config->get('image.disk'))->path($screen_paths->getFilePathUrl()));

        return $screen_paths;
    }

    public function getFilePath(IAddScreens $screen_files_provider, $size_separator): string
    {
        $project = $this->getProject($screen_files_provider);
        $company_name = $this->getCompanyName($project);

        $parts_of_path = [];
        array_push(
            $parts_of_path,
            $this->config->get('image.folder_main'),
            implode(self::UNDERSCORE, [$project->short_name, $company_name]),
            $this->user->id,
            $size_separator,
            $screen_files_provider->getNameScreen()
        );

        return implode(self::DIRECTORY_SEPARATOR, $parts_of_path);
    }
}
