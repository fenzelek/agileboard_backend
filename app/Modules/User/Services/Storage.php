<?php

namespace App\Modules\User\Services;

use Exception;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Models\Db\User as ModelUser;
use App\Services\Storage as BaseStorage;

class Storage extends BaseStorage
{
    /**
     * Update avatar.
     *
     * @param ModelUser $user
     * @param UploadedFile $avatar
     *
     * @return string
     * @throws Exception
     */
    public function updateAvatar(ModelUser $user, UploadedFile $avatar)
    {
        $avatar_name = $this->getFileName('avatar', '', $user->id, $avatar, true, false);

        // save avatar to storage
        $this->putFileAs('avatar', '', $avatar, $avatar_name);

        // check that the avatar has been saved
        if (! $this->model_store->fileExists('avatar', '', $avatar_name)) {
            throw new Exception('Failed to save file.');
        }

        // resize avatar
        $this->resizeAvatar($avatar_name);

        return $avatar_name;
    }

    /**
     * Resize avatar.
     *
     * @param $avatar_name
     */
    protected function resizeAvatar($avatar_name)
    {
        $image = Image::make($this->filesystem->disk('avatar')->get($avatar_name));
        $width = $image->width();
        $height = $image->height();

        // Resize image only when width or height are bigger than 200px.
        if ($width > 200 || $height > 200) {
            if ($width >= $height) {
                $image->resize(200, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize(null, 200, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $path = $this->filesystem->disk('avatar')->getDriver()->getAdapter()->getPathPrefix();
            $image->save($path . '/' . $avatar_name);
        }
    }
}
