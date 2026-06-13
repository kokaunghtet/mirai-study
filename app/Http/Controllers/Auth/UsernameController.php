<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UsernameController extends Controller
{
    /**
     * Suggest a few available, alphanumeric usernames based on a display name.
     * e.g. "Kaung Htet" -> ["kaunghtet", "kaunghtet2004", "kaunghtet87"].
     */
    public function suggestions(Request $request): JsonResponse
    {
        $base = $this->baseFrom((string) $request->query('name', ''));

        $suggestions = [];

        // Offer the bare handle first when it's both long enough and free.
        if (strlen($base) >= 3 && ! $this->taken($base)) {
            $suggestions[] = $base;
        }

        for ($i = 0; count($suggestions) < 3 && $i < 40; $i++) {
            $candidate = substr($base.random_int(1, 9999), 0, 30);
            if (strlen($candidate) < 3 || in_array($candidate, $suggestions, true)) {
                continue;
            }
            if (! $this->taken($candidate)) {
                $suggestions[] = $candidate;
            }
        }

        return response()->json(['usernames' => array_values($suggestions)]);
    }

    /**
     * Report whether a username is well-formed and still available.
     */
    public function available(Request $request): JsonResponse
    {
        $username = strtolower((string) $request->query('username', ''));
        $valid = (bool) preg_match('/^[a-z0-9]{3,30}$/', $username);

        return response()->json([
            'valid' => $valid,
            'available' => $valid && ! $this->taken($username),
        ]);
    }

    private function baseFrom(string $name): string
    {
        $base = Str::of($name)->lower()->replaceMatches('/[^a-z0-9]/', '')->substr(0, 15)->value();

        return $base !== '' ? $base : 'user';
    }

    private function taken(string $username): bool
    {
        // withTrashed: the unique index covers soft-deleted rows too.
        return User::withTrashed()->where('username', $username)->exists();
    }
}
