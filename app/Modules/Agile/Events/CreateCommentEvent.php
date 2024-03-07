<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;

class CreateCommentEvent extends AbstractCommentEvent
{
    public function getMessage(): array
    {
        $comment = strip_tags($this->comment->text);

        if (mb_strlen($comment) > 80) {
            $comment = mb_substr($comment, 0, 80) . '...';
        }

        $data = [
            'comment' => $comment,
            'first_name' => $this->comment->user->first_name,
            'last_name' => $this->comment->user->last_name,
        ];

        return $this->generateMessage($data);
    }

    public function getType(): string
    {
        return EventTypes::TICKET_COMMENT_STORE;
    }
}
