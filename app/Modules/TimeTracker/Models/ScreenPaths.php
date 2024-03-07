<?php

namespace App\Modules\TimeTracker\Models;

use App\Modules\TimeTracker\Models\Contracts\IScreenPaths;

class ScreenPaths implements IScreenPaths
{
    private string $file_path_url;
    private string $storage_path_url;
    private string $storage_link_url;
    private string $file_path_thumb;
    private string $storage_path_thumb;
    private string $storage_link_thumb;
    private string $screen_name;
    private bool $valid;

    /**
     * @return string
     */
    public function getFilePathUrl(): string
    {
        return $this->file_path_url;
    }

    /**
     * @param string $file_path_url
     */
    public function setFilePathUrl(string $file_path_url): void
    {
        $this->file_path_url = $file_path_url;
    }

    /**
     * @return string
     */
    public function getStoragePathUrl(): string
    {
        return $this->storage_path_url;
    }

    /**
     * @param string $storage_path_url
     */
    public function setStoragePathUrl(string $storage_path_url): void
    {
        $this->storage_path_url = $storage_path_url;
    }

    /**
     * @return string
     */
    public function getStorageLinkUrl(): string
    {
        return $this->storage_link_url;
    }

    /**
     * @param string $storage_link_url
     */
    public function setStorageLinkUrl(string $storage_link_url): void
    {
        $this->storage_link_url = $storage_link_url;
    }

    /**
     * @return string
     */
    public function getFilePathThumb(): string
    {
        return $this->file_path_thumb;
    }

    /**
     * @param string $file_path_thumb
     */
    public function setFilePathThumb(string $file_path_thumb): void
    {
        $this->file_path_thumb = $file_path_thumb;
    }

    /**
     * @return string
     */
    public function getStoragePathThumb(): string
    {
        return $this->storage_path_thumb;
    }

    /**
     * @param string $storage_path_thumb
     */
    public function setStoragePathThumb(string $storage_path_thumb): void
    {
        $this->storage_path_thumb = $storage_path_thumb;
    }

    /**
     * @return string
     */
    public function getStorageLinkThumb(): string
    {
        return $this->storage_link_thumb;
    }

    /**
     * @param string $storage_link_thumb
     */
    public function setStorageLinkThumb(string $storage_link_thumb): void
    {
        $this->storage_link_thumb = $storage_link_thumb;
    }

    /**
     * @return string
     */
    public function getScreenName(): string
    {
        return $this->screen_name;
    }

    /**
     * @param string $screen_name
     */
    public function setScreenName(string $screen_name): void
    {
        $this->screen_name = $screen_name;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }
}
