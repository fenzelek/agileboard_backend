<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemManager as Filesystem;
use App\Models\Filesystem\Store as ModelStore;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Storage
{
    const MAX_ITERATION = 2;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ModelStore
     */
    protected $model_store;

    /**
     * Storage constructor.
     *
     * @param ModelStore $model_store
     * @param Filesystem $filesystem
     */
    public function __construct(ModelStore $model_store, Filesystem $filesystem)
    {
        $this->model_store = $model_store;
        $this->filesystem = $filesystem;
    }

    /**
     * Delete file.
     *
     * @param string $disk
     * @param string $directory
     * @param string $file
     *
     * @throws Exception
     */
    public function deleteFile($disk, $directory = '', $file)
    {
        $this->filesystem->disk($disk)->delete($directory . $file);

        // check that the file has been deleted
        if ($this->model_store->fileExists($disk, $directory, $file)) {
            throw new Exception('Failed to delete file');
        }
    }

    /**
     * Loop while is running in the specified iteration, generating a unique file name
     * that will be saved in the storage. When the generated name is not unique, then in
     * the next iteration of the loop, before the file name is added timestamp value.
     *
     * @param string $disk
     * @param string $directory
     * @param int $name
     * @param UploadedFile $file
     * @param bool $file_exists
     * @param bool $check_extension
     *
     * @return string
     * @throws Exception
     */
    protected function getFileName(
        $disk,
        $directory,
        $name,
        UploadedFile $file,
        $file_exists = false,
        $check_extension = true
    ) {
        $i = 1;

        do {
            if ($i > self::MAX_ITERATION) {
                throw new Exception('The name generation limit was exceeded');
            }

            $file_name = $this->getUniqueName($name, $file, $file_exists, $check_extension);

            ++$i;
        } while ($file_exists = $this->model_store->fileExists($disk, $directory, $file_name));

        return $file_name;
    }

    /**
     * Create a unique file name used for writing to disk.
     *
     * @param int $name
     * @param UploadedFile $file
     * @param bool $add_timestamp
     * @param bool $check_extension
     *
     * @return string
     */
    protected function getUniqueName(
        $name,
        UploadedFile $file,
        $add_timestamp,
        $check_extension = false
    ) {
        if ($add_timestamp) {
            $name = Carbon::now()->timestamp . '_' . $name;
        }

        if ($check_extension) {
            if (in_array($file->getClientOriginalExtension(), $this->extension->denied())) {
                return $name;
            }
        }

        return $name . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Put file to disk.
     *
     * @param $disk
     * @param $directory
     * @param UploadedFile $file
     * @param $file_name
     */
    protected function putFileAs($disk, $directory, UploadedFile $file, $file_name)
    {
        $this->filesystem->disk($disk)->putFileAs($directory, $file, $file_name);
    }
}
