<?php

namespace App\Modules\Notification\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MissingNotificationKeyException extends HttpException
{
    const MESSAGE = 'Missing notification data key: ';

    public function __construct(string $missing_key, \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct(400, self::MESSAGE . $missing_key, null, []);
    }
}
