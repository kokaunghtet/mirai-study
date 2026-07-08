<?php

if (! function_exists('linkify_urls')) {
    /**
     * Convert bare URLs in plain text to clickable <a> tags.
     * Matches against the raw text and escapes each segment (URL or plain)
     * exactly once, so entities in query strings (e.g. `&`) aren't double-encoded.
     */
    function linkify_urls(?string $text, bool $truncated = false): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $pattern = '/(https?:\/\/[^\s<>\x{2026}]+)/iu';

        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        // preg_split leaves a trailing empty segment when a match ends the
        // string — skip it so the "is this the last URL match" check below
        // actually lands on the URL, not the empty tail.
        $lastIndex = count($parts) - 1;
        if ($lastIndex >= 0 && $parts[$lastIndex] === '') {
            $lastIndex--;
        }
        $html = '';

        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $html .= e($part);

                continue;
            }

            // Str::limit() truncation appends '...' directly after the cut,
            // so when the caller tells us the text was truncated, a URL
            // match ending the string with a trailing ellipsis is a
            // truncated/incomplete URL — don't link it.
            if ($truncated && $i === $lastIndex && str_ends_with($part, '...')) {
                $html .= e($part);

                continue;
            }

            $url = rtrim($part, '.,:;!?)]}\'"');

            if ($url === '') {
                $html .= e($part);

                continue;
            }

            $html .= '<a href="'.e($url).'" target="_blank" rel="nofollow noopener noreferrer" class="linkified-url">'.e($url).'</a>';
        }

        return $html;
    }
}
