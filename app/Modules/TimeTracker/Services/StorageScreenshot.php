<?php

namespace App\Modules\TimeTracker\Services;

use App\Modules\TimeTracker\Http\Requests\Contracts\IAddScreens;
use App\Modules\TimeTracker\Models\Contracts\IScreenPaths;
use App\Modules\TimeTracker\Models\Contracts\IScreenDBSaver;
use App\Modules\TimeTracker\Services\Contracts\IStorageScreenshot;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class StorageScreenshot implements IStorageScreenshot
{
    private ImageManager $image_manager;
    private ConfigContract $config;
    private PathGenerator $builder;
    private Storage $storage;

    public function __construct(ConfigContract $config, PathGenerator $builder, ImageManager $image_manager, Storage $storage)
    {
        $this->config = $config;
        $this->builder = $builder;
        $this->image_manager = $image_manager;
        $this->storage = $storage;
    }

    public function addScreenshot(IAddScreens $screen_files_provider, IScreenDBSaver $screen_service): bool
    {
        $screen_paths = $this->builder->pathBuilder($screen_files_provider);

        if ($screen_paths->isValid()) {
            $screen_paths = $this->storeImage($screen_paths, $screen_files_provider);
        }

        return $screen_service->saveScreen($screen_paths);
    }

    public function storeImage(IScreenPaths $screen_paths, IAddScreens $screen_files_provider)
    {
        $thumb_width = $this->config->get('image.thumb_width');
        $url_height = $this->config->get('image.url_height');

        try {
            $img_thumb = $this->image_manager->make($screen_files_provider->getScreenshot());
            $img_thumb = $img_thumb->resize($thumb_width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $this->storage::disk($this->config->get('image.disk'))->put($screen_paths->getFilePathThumb(), $img_thumb->stream());
            $screen_paths->setStorageLinkThumb($this->storage::url($screen_paths->getFilePathThumb()));

            $img_url = $this->image_manager->make($screen_files_provider->getScreenshot());
            $img_url = $img_url->resize(null, $url_height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $this->storage::disk($this->config->get('image.disk'))->put($screen_paths->getFilePathUrl(), $img_url->stream());
            $screen_paths->setStorageLinkUrl($this->storage::url($screen_paths->getFilePathUrl()));

            return $screen_paths;
        } catch (\Exception $e) {
            Log::critical('Cannot add image ' . $screen_paths->getScreenName());
            Log::critical('Object ScreenPaths', [$screen_paths]);

            $screen_paths->setValid(false);

            return $screen_paths;
        }
    }
}
