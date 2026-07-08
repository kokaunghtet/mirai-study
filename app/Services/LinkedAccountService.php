<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Tracks which accounts have been fully authenticated (password/OTP passed) in this
 * browser session, so the sidebar switcher can hop between them without a fresh login.
 */
class LinkedAccountService
{
    protected const SESSION_KEY = 'linked_accounts';

    /** Remember a user as switchable in this session, most-recent first. */
    public function remember(Request $request, User $user): void
    {
        $ids = $request->session()->get(self::SESSION_KEY, []);

        $ids = array_values(array_unique(array_merge([$user->id], $ids)));

        $request->session()->put(self::SESSION_KEY, $ids);
    }

    /** Whether a user id has been authenticated in this session and can be switched to. */
    public function contains(Request $request, int $userId): bool
    {
        return in_array($userId, $request->session()->get(self::SESSION_KEY, []), true);
    }

    /**
     * Switchable accounts for this browser session, current user first. Re-fetched from
     * the database on every call so display fields (name, avatar) never go stale — the
     * session only ever stores the id list.
     */
    public function accounts(Request $request): Collection
    {
        if (! $request->user()) {
            return collect();
        }

        $ids = $request->session()->get(self::SESSION_KEY, []);
        $ids = array_values(array_unique(array_merge([$request->user()->id], $ids)));

        $users = User::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $users->get($id))
            ->filter(fn (?User $user) => $user && ! $user->isBanned())
            ->values();
    }
}
