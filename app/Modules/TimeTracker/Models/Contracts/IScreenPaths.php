<?php

namespace App\Modules\TimeTracker\Models\Contracts;

interface IScreenPaths
{
    public function getFilePathUrl(): string;

    public function setFilePathUrl(string $file_path_url): void;

    public function getStoragePathUrl(): string;

    public function setStoragePathUrl(string $storage_path_url): void;

    public function getStorageLinkUrl(): string;

    public function setStorageLinkUrl(string $storage_link_url): void;

    public function getFilePathThumb(): string;

    public function setFilePathThumb(string $file_path_thumb): void;

    public function getStoragePathThumb(): string;

    public function setStoragePathThumb(string $storage_path_thumb): void;

    public function getStorageLinkThumb(): string;

    public function setStorageLinkThumb(string $storage_link_thumb): void;

    public function getScreenName(): string;

    public function setScreenName(string $screen_name): void;

    public function isValid(): bool;

    public function setValid(bool $valid): void;
}
