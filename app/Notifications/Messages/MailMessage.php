<?php

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage as IlluminateMailMessage;

class MailMessage extends IlluminateMailMessage
{
    /**
     * @var null
     */
    public $viewMoreData;

    /**
     * Show regards.
     *
     * @param $regards
     * @return $this
     */
    public function regards($regards)
    {
        $this->viewMoreData['regards'] = $regards;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function data()
    {
        return array_merge($this->toArray(), $this->viewData, $this->viewMoreData);
    }
}
