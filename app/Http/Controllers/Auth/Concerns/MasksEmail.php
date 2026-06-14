<?php

namespace App\Http\Controllers\Auth\Concerns;

trait MasksEmail
{
    /**
     * Turn "kaung@example.com" into "k•••g@example.com" for display.
     */
    protected function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($name === '' || $domain === '') {
            return $email;
        }

        $visible = mb_strlen($name) <= 2
            ? mb_substr($name, 0, 1)
            : mb_substr($name, 0, 1).str_repeat('•', 3).mb_substr($name, -1);

        return $visible.'@'.$domain;
    }
}
