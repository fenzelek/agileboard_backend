<?php

namespace App\Models\Filesystem;

use Illuminate\Filesystem\FilesystemManager as Filesystem;

class Store
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Store constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param int $company_id
     * @param int $project_id
     *
     * @return string, path to file
     */
    public function getPath($company_id, $project_id)
    {
        $directory = $company_id . '/projects/' . $project_id;

        return $directory;
    }

    /**
     * @param string $disk
     * @param string $directory
     * @param string $file_name
     *
     * @return bool
     */
    public function fileExists($disk, $directory, $file_name)
    {
        return $this->filesystem->disk($disk)->exists($directory . $file_name);
    }
}
