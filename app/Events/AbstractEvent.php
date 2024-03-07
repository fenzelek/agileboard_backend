<?php

namespace App\Events;

use App\Models\Db\Project;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

abstract class AbstractEvent implements EventInterface
{
    use Dispatchable, SerializesModels;

    public $project;
    public $ticket;

    /**
     * Return project.
     *
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * Return attachemnts.
     *
     * @return array
     */
    public function getAttachments(): array
    {
        return [];
    }

    /**
     * Add unique recipient.
     *
     * @param $recipients
     * @param $user
     * @param bool $in_project
     * @return mixed
     */
    protected function addRecipient($recipients, $user, $in_project = true)
    {
        if ($user) {
            //check user is in project
            if ($in_project) {
                if (! $this->project->users()->where('user_id', $user->id)->first()) {
                    return $recipients;
                }
            }

            $found = false;
            foreach ($recipients as $recipient) {
                if ($recipient->id == $user->id) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $recipients->push($user);
            }
        }

        return $recipients;
    }

    /**
     * Remove recipient.
     *
     * @param $recipients
     * @param $user_id
     * @return array
     */
    protected function removeRecipient($recipients, $user_id)
    {
        foreach ($recipients as $key => $recipient) {
            if ($recipient->id == $user_id) {
                $recipients = $recipients->except([$key]);
                break;
            }
        }

        return $recipients;
    }
}
