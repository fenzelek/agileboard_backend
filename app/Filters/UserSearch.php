<?php

namespace App\Filters;

trait UserSearch
{
    public function applySearch($value)
    {
        $words = explode(' ', trim($value));

        if (! $words) {
            return;
        }

        $this->query->where(function ($q) use ($words) {
            $q->whereHas('user', function ($q) use ($words) {
                $first = true;
                foreach ($words as $word) {
                    $word = trim($word);
                    if (! $word) {
                        continue;
                    }
                    $operator = $first ? 'where' : 'orWhere';

                    $q->$operator('first_name', 'LIKE', '%' . $word . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $word . '%')
                        ->orWhere('email', 'LIKE', '%' . $word . '%');

                    $first = false;
                }
            });
        });
    }
}
