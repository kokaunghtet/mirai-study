<?php

return [

    // Kill switch (seeding, local dev).
    'enabled' => env('MODERATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklisted words and phrases
    |--------------------------------------------------------------------------
    |
    | - ASCII entries are matched whole-word with leetspeak (sh1t), separator
    |   (s.h.i.t), and repeated-char (shiiit) tolerance.
    | - Entries with spaces are phrases; each space matches 1-3 separator chars.
    | - Entries containing non-ASCII chars (Myanmar, CJK, …) are matched as
    |   case-insensitive substrings (those scripts lack word boundaries).
    | - Curation note: very short entries (2-3 letters) carry the highest
    |   false-positive risk even with boundary checks.
    |
    */
    // 'blacklist' => [
    //     'shit',
    //     'fuck',
    //     'bitch',
    //     'cunt',
    //     'asshole',
    //     'lee',
    //     'dick',
    // ],

    'blacklist' => [
        // General profanity
        'ass',
        'asshole',
        'arse',
        'arsehole',
        'bastard',
        'bitch',
        'bullshit',
        'crap',
        'cunt',
        'damn',
        'dick',
        'dickhead',
        'dildo',
        'dipshit',
        'fag',
        'faggot',
        'fuck',
        'fucker',
        'fucking',
        'fucker',
        'goddamn',
        'hell',
        'horseshit',
        'jackass',
        'motherfucker',
        'motherfucking',
        'nigga',
        'nigger',
        'piss',
        'prick',
        'pussy',
        'retard',
        'retarded',
        'shit',
        'shithead',
        'slut',
        'sonofabitch',
        'twat',
        'wanker',
        'whore',

        // Sexual
        'anal',
        'blowjob',
        'boobs',
        'cock',
        'cum',
        'cumming',
        'cunt',
        'deepthroat',
        'ejaculate',
        'handjob',
        'masturbate',
        'masturbation',
        'orgasm',
        'penis',
        'porn',
        'porno',
        'pornography',
        'sex',
        'vagina',

        // Offensive insults
        'bimbo',
        'clown',
        'dumbass',
        'idiot',
        'imbecile',
        'loser',
        'moron',
        'scumbag',
        'stupid',

        // Your custom words
        'lee',
        'soke',
        'လီး',        
    ],

];
