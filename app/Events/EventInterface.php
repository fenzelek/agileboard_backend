<?php

namespace  App\Events;

use App\Models\Db\Project;

interface EventInterface
{
    public function getProject() : Project;

    public function getMessage() : array;

    public function getRecipients();

    public function getType() : string;

    public function getAttachments() : array;

    public function getBroadcastChannel() : string;

    public function getBroadcastData() : array;
}
