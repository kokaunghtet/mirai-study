<?php

namespace App\Rules;

use App\Services\ProfanityFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoProfanity implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('moderation.enabled', true) || ! is_string($value) || $value === '') {
            return; // required/string rules own those failures
        }

        // Message deliberately does not echo the matched word — that would
        // leak the blacklist.
        if (app(ProfanityFilter::class)->contains($value)) {
            $fail('The :attribute contains language that is not allowed. Please revise it and try again.');
        }
    }
}
