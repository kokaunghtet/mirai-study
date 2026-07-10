<?php

namespace Tests\Unit;

use App\Services\ProfanityFilter;
use PHPUnit\Framework\TestCase;

class ProfanityFilterTest extends TestCase
{
    private ProfanityFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new ProfanityFilter(['shit', 'cunt', 'ass', 'buy followers', 'クソ']);
    }

    public function test_matches_plain_word(): void
    {
        $this->assertTrue($this->filter->contains('this is shit'));
        $this->assertSame('shit', $this->filter->firstMatch('this is shit'));
    }

    public function test_matches_regardless_of_case(): void
    {
        $this->assertTrue($this->filter->contains('ShIt'));
    }

    public function test_matches_leetspeak_variants(): void
    {
        $this->assertTrue($this->filter->contains('sh1t'));
        $this->assertTrue($this->filter->contains('$h!t'));
    }

    public function test_matches_separator_variants(): void
    {
        $this->assertTrue($this->filter->contains('s h i t'));
        $this->assertTrue($this->filter->contains('s.h.i.t'));
        $this->assertTrue($this->filter->contains('s-h-i-t'));
    }

    public function test_matches_repeated_characters(): void
    {
        $this->assertTrue($this->filter->contains('shiiit'));
        $this->assertTrue($this->filter->contains('shhhiiittt'));
    }

    public function test_matches_combined_evasion(): void
    {
        $this->assertTrue($this->filter->contains('S h 1 i T'));
    }

    public function test_matches_fullwidth_characters(): void
    {
        if (! class_exists(\Normalizer::class)) {
            $this->markTestSkipped('ext-intl not installed; fullwidth folding unavailable.');
        }

        $this->assertTrue($this->filter->contains('ｓｈｉｔ'));
    }

    public function test_matches_word_adjacent_to_cjk_text(): void
    {
        $this->assertTrue($this->filter->contains('これはshitです'));
    }

    public function test_matches_phrase_with_varied_separators(): void
    {
        $this->assertTrue($this->filter->contains('buy   followers'));
        $this->assertTrue($this->filter->contains('buy-followers'));
    }

    public function test_matches_non_latin_entry_as_substring(): void
    {
        $this->assertTrue($this->filter->contains('これはクソだ'));
    }

    public function test_does_not_flag_scunthorpe_style_words(): void
    {
        $this->assertFalse($this->filter->contains('class'));
        $this->assertFalse($this->filter->contains('assessment'));
        $this->assertFalse($this->filter->contains('Scunthorpe'));
        $this->assertFalse($this->filter->contains('bass'));
        $this->assertFalse($this->filter->contains('passive'));
        $this->assertFalse($this->filter->contains('classic assignment'));
    }

    public function test_does_not_match_across_word_fragments(): void
    {
        // "mass hit" contains ass + hit but no standalone match:
        // in-word boundaries block both fragments.
        $this->assertFalse($this->filter->contains('mass hit incoming'));
    }

    public function test_does_not_flag_similar_phrase(): void
    {
        $this->assertFalse($this->filter->contains('buyer follows'));
    }

    public function test_clean_unicode_text_passes_without_error(): void
    {
        $this->assertFalse($this->filter->contains('日本語の勉強を頑張ります'));
        $this->assertFalse($this->filter->contains('မင်္ဂလာပါ၊ စာမေးပွဲအတွက် ကြိုးစားနေပါတယ်'));
    }

    public function test_empty_and_blank_input(): void
    {
        $this->assertFalse($this->filter->contains(''));
        $this->assertFalse($this->filter->contains('   '));
    }

    public function test_numbers_and_symbols_alone_are_not_profanity(): void
    {
        // Leet classes accept digits (4→a, 5→s, 3→e, 1→l/i); the
        // min-letters guard must keep plain numbers and tables clean.
        $filter = new ProfanityFilter(['ass', 'sex', 'lee']);

        $this->assertFalse($filter->contains('455'));
        $this->assertFalse($filter->contains('4.55'));
        $this->assertFalse($filter->contains('score was 45 5 points'));
        $this->assertFalse($filter->contains('53x + 7 = 12'));
        $this->assertFalse($filter->contains('| 33 | 40 |'));
        $this->assertFalse($filter->contains('1.33'));

        // Mostly-letters variants must still be caught.
        $this->assertTrue($filter->contains('a5s'));
        $this->assertTrue($filter->contains('s3x'));
        $this->assertTrue($filter->contains('Lee'));
    }

    public function test_duplicate_blacklist_entries_are_deduplicated(): void
    {
        $filter = new ProfanityFilter(['shit', 'shit', 'SHIT']);

        $this->assertTrue($filter->contains('shit'));
        $this->assertFalse($filter->contains('clean text'));
    }

    public function test_empty_blacklist_matches_nothing(): void
    {
        $filter = new ProfanityFilter([]);

        $this->assertFalse($filter->contains('this is shit'));
    }
}
