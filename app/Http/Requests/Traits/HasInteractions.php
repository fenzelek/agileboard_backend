<?php

declare(strict_types=1);

namespace App\Http\Requests\Traits;

use App\Models\Db\User;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Modules\Agile\Http\Requests\InteractionPingRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

trait HasInteractions
{
    private ?Collection $interaction_items=null;

    private function interactionRules(): array
    {
        return [
            'interactions' => ['nullable','array'],
            'interactions.data' => ['nullable','array'],
            'interactions.data.*.ref' => ['required', 'string'],
            'interactions.data.*.notifiable' => ['required', 'string' , Rule::in(NotifiableType::USER, NotifiableType::GROUP)],
            'interactions.data.*.message'  => ['nullable', 'string', 'max:500'],
            'interactions.data.*.recipient_id' => ['required','integer', $this->getRecipientValidationCallback()],
        ];
    }

    private function prepareInteractionItems(): void
    {
        $this->interaction_items = new Collection();
        foreach ($this->input('interactions.data', []) as $item) {
            $this->interaction_items->add(new InteractionPingRequest($item));
        }
    }

    private function getRecipientValidationCallback(): callable
    {
        return function ($attribute, $value, $fail) {
            $notifiable = $this->input('interactions.data.*.notifiable');
            $orderIndex = (int) (explode('.', $attribute)[2]);
            if (($notifiable[$orderIndex] == NotifiableType::GROUP
                    && ! in_array($value, NotifiableGroupType::all()))
                || ($notifiable[$orderIndex] == NotifiableType::USER
                    && ! User::query()->whereKey($value)->exists())) {
                $fail('Interaction recipient ' . $value . ' not found.');
            }
        };
    }
}
