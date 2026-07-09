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

    public const int MAX_ACCOUNTS = 2;

    /**
     * Whether another account can be added to this session. Counts only accounts that
     * still resolve to real, unbanned users — a linked account that was since banned or
     * deleted must not occupy a slot forever. Re-authenticating an account that is
     * already linked never needs a free slot, so pass its id when known.
     */
    public function canAdd(Request $request, ?int $userId = null): bool
    {
        $accounts = $this->accounts($request);

        if ($userId !== null && $accounts->contains('id', $userId)) {
            return true;
        }

        return $accounts->count() < self::MAX_ACCOUNTS;
    }

    /** Remember a user as switchable in this session, most-recent first. */
    public function remember(Request $request, User $user): void
    {
        $ids = $request->session()->get(self::SESSION_KEY, []);

        $ids = array_values(array_unique(array_merge([$user->id], $ids)));

        $request->session()->put(self::SESSION_KEY, $ids);
    }

    /** Remove a user from the session's linked accounts. Cannot remove the current user. */
    public function forget(Request $request, int $userId): void
    {
        $ids = array_values(array_filter(
            $request->session()->get(self::SESSION_KEY, []),
            fn ($id) => $id !== $userId,
        ));

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

        $accounts = collect($ids)
            ->map(fn ($id) => $users->get($id))
            ->filter(fn (?User $user) => $user && ! $user->isBanned())
            ->values();

        // Write the surviving ids back: banned/deleted accounts would otherwise hold a
        // MAX_ACCOUNTS slot forever — they're hidden from the switcher, so their remove
        // button never renders, and route binding 404s on soft-deleted users anyway.
        $request->session()->put(self::SESSION_KEY, $accounts->pluck('id')->all());

        return $accounts;
    }
}
