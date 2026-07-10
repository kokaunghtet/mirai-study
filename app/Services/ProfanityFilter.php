<?php

namespace App\Services;

class ProfanityFilter
{
    /**
     * Characters users insert between letters to evade detection.
     */
    private const SEPARATOR = '[\s.\-_*,]';

    /**
     * Leetspeak substitutions folded into each letter's character class.
     * None of these substitution characters overlap with SEPARATOR, so the
     * compiled patterns stay unambiguous (no backtracking blowups).
     */
    private const LEET = [
        'a' => '[a@4]',
        'b' => '[b8]',
        'e' => '[e3]',
        'g' => '[g9]',
        'i' => '[i1!|]',
        'l' => '[l1|]',
        'o' => '[o0]',
        's' => '[s5$]',
        't' => '[t7+]',
    ];

    /**
     * @var list<array{entry: string, pattern: ?string, min_letters: int}>|null
     */
    private ?array $compiled = null;

    /**
     * @param  list<string>  $blacklist
     */
    public function __construct(private readonly array $blacklist) {}

    public function contains(string $text): bool
    {
        return $this->firstMatch($text) !== null;
    }

    /**
     * Returns the blacklist entry the text violates, or null when clean.
     */
    public function firstMatch(string $text): ?string
    {
        if ($this->blacklist === [] || trim($text) === '') {
            return null;
        }

        $normalized = $this->normalize($text);

        foreach ($this->compile() as $compiled) {
            $found = $compiled['pattern'] !== null
                ? $this->matchesPattern($compiled, $normalized)
                : mb_stripos($normalized, $this->normalize($compiled['entry'])) !== false;

            if ($found) {
                return $compiled['entry'];
            }
        }

        return null;
    }

    /**
     * A regex hit only counts when the matched text contains at least half
     * the entry's length in real letters. The leet classes accept digits
     * (4→a, 5→s, 3→e, 1→l…), so without this guard innocent numbers match:
     * "455"/"4.55" as "ass", "53x" as "sex", "| 33" as "lee".
     *
     * @param  array{entry: string, pattern: ?string, min_letters: int}  $compiled
     */
    private function matchesPattern(array $compiled, string $text): bool
    {
        // Falsy check (not === 0) so a preg failure (false) bails out too
        // instead of iterating a null $matches[0].
        if (! preg_match_all($compiled['pattern'], $text, $matches)) {
            return false;
        }

        foreach ($matches[0] as $match) {
            if (preg_match_all('/[a-z]/', $match) >= $compiled['min_letters']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{entry: string, pattern: ?string}>
     */
    private function compile(): array
    {
        if ($this->compiled !== null) {
            return $this->compiled;
        }

        $this->compiled = [];
        $seen = [];

        foreach ($this->blacklist as $entry) {
            if (! is_string($entry) || trim($entry) === '') {
                continue;
            }

            $entry = trim($entry);
            $lower = mb_strtolower($entry);

            if (isset($seen[$lower])) {
                continue;
            }
            $seen[$lower] = true;

            $this->compiled[] = [
                'entry' => $entry,
                'pattern' => $this->patternFor($lower),
                'min_letters' => (int) ceil(mb_strlen(str_replace(' ', '', $lower)) / 2),
            ];
        }

        return $this->compiled;
    }

    /**
     * Build a whole-word pattern tolerant of leetspeak, separators, and
     * repeated characters. Non-ASCII entries return null and fall back to
     * substring matching (their scripts have no word boundaries).
     */
    private function patternFor(string $entry): ?string
    {
        if (preg_match('/^[\x20-\x7e]+$/', $entry) !== 1) {
            return null;
        }

        $atoms = [];

        foreach (str_split($entry) as $char) {
            if ($char === ' ') {
                // Phrase gap: require at least one separator.
                $atoms[] = self::SEPARATOR.'{1,3}';

                continue;
            }

            $class = self::LEET[$char] ?? preg_quote($char, '/');
            // X(?:sep{0,3}X)* absorbs repeats even across separators
            // ("shiiit", "s h 1 i t"). The {0,3} bound keeps a spaced-out
            // word from matching across a whole paragraph.
            $atoms[] = $class.'(?:'.self::SEPARATOR.'{0,3}'.$class.')*';
        }

        $body = implode(self::SEPARATOR.'{0,3}', $atoms);

        // ASCII lookarounds (not \p{L}) so adjacent CJK/Myanmar text cannot
        // mask a match, while "class"/"assessment" still fail the boundary.
        return '/(?<![a-z0-9])'.$body.'(?![a-z0-9])/iu';
    }

    private function normalize(string $text): string
    {
        // NFKC folds fullwidth chars (ｓｈｉｔ → shit); needs ext-intl.
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }

        return mb_strtolower($text);
    }
}
