<?php

namespace App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Integration\Integration;
use Illuminate\Support\Collection;
use App\Models\Db\Integration\TimeTracking\Note;

class NotesProcessor
{
    /**
     * @var Note
     */
    protected $note;

    /**
     * NotesProcessor constructor.
     *
     * @param Note $note
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Save notes.
     *
     * @param Integration $integration
     * @param Collection $notes
     */
    public function save(Integration $integration, Collection $notes)
    {
        $notes->each(function ($note) use ($integration) {
            /** @var \App\Models\Other\Integration\TimeTracking\Note $note */
            $note_model = $this->note->where('integration_id', $integration->id)
                ->where('external_note_id', $note->getExternalId())->first();

            // if note already exists we will update its content but not project, user or time
            if ($note_model) {
                $note_model->update([
                    'content' => $note->getContent(),
                ]);

                return true;
            }

            // otherwise new note will be created
            $this->note->create([
                'integration_id' => $integration->id,
                'external_note_id' => $note->getExternalId(),
                'external_project_id' => $note->getExternalProjectId(),
                'external_user_id' => $note->getExternalUserId(),
                'content' => $note->getContent(),
                'utc_recorded_at' => $note->getUtcRecordedAt(),
            ]);
        });
    }
}
