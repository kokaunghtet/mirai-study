<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // ── Public profile page ──────────────────────────────
    public function show(Request $request, User $user)
    {
        $user->loadCount(['posts', 'followers', 'following']);

        $preferences = $user->preferences;
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;
        $isFollowing = false;

        if (auth()->check() && ! $isOwnProfile) {
            $isFollowing = auth()->user()
                ->following()
                ->where('following_id', $user->id)
                ->exists();
        }

        // Liked tab visibility
        $showLikedTab = $isOwnProfile || ($preferences?->show_liked_posts ?? true);

        // Active tab — fall back if liked tab is hidden
        $tab = $request->get('tab', 'posts');
        if ($tab === 'liked' && ! $showLikedTab) {
            $tab = 'posts';
        }

        // Constrained eager loads for auth users
        $with = ['user', 'tags', 'media'];
        if (auth()->check()) {
            $userId = auth()->id();
            $with['bookmarks'] = fn ($q) => $q->where('user_id', $userId);
            $with['likes'] = fn ($q) => $q->where('user_id', $userId);
        }

        if ($tab === 'posts') {
            $posts = Post::where('user_id', $user->id)
                ->with($with)
                ->withCount(['likes', 'comments', 'bookmarks'])
                ->latest()
                ->paginate(10);
        } else {
            $posts = $user->likedPosts()
                ->with($with)
                ->withCount(['likes', 'comments', 'bookmarks'])
                ->whereHas('user', fn ($q) => $q->whereNull('users.deleted_at'))
                ->latest('post_likes.created_at')
                ->paginate(10);
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('profile._posts', compact('posts'))->render(),
                'next_page_url' => $posts->nextPageUrl(),
            ]);
        }

        return view('profile.show', compact(
            'user',
            'isOwnProfile',
            'isFollowing',
            'showLikedTab',
            'tab',
            'posts'
        ));
    }

    // ── Followers list ───────────────────────────────────
    public function followers(User $user)
    {
        $user->loadCount(['posts', 'followers', 'following']);

        $followers = $user->followers()->paginate(20);

        $authFollowingIds = auth()->check()
            ? auth()->user()->following()->pluck('users.id')->all()
            : [];

        return view('profile.followers', compact('user', 'followers', 'authFollowingIds'));
    }

    // ── Following list ───────────────────────────────────
    public function following(User $user)
    {
        $user->loadCount(['posts', 'followers', 'following']);

        $following = $user->following()->paginate(20);

        $authFollowingIds = auth()->check()
            ? auth()->user()->following()->pluck('users.id')->all()
            : [];

        return view('profile.following', compact('user', 'following', 'authFollowingIds'));
    }

    // ── Edit profile settings (private) ─────────────────
    public function edit(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences ?? UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['theme_mode' => 'light', 'accent_color' => 'venom', 'fill_style' => 'gradient', 'show_liked_posts' => true]
        );

        return view('profile.edit', compact('user', 'preferences'));
    }

    // ── Save profile settings ────────────────────────────
    public function update(Request $request)
    {
        $user = $request->user();

        // Normalise to the canonical lowercase form before comparing/validating,
        // so the strict format path matches registration behaviour and the
        // "unchanged?" check is case-insensitive.
        if ($request->filled('username')) {
            $request->merge(['username' => strtolower($request->input('username'))]);
        }

        // Only enforce the strict FORMAT when the user is actually changing their
        // handle. Users whose existing username predates the current rules (e.g.
        // legacy dots/underscores or >30 chars) can still edit the rest of their
        // profile; a *new* username must conform.
        $usernameRules = ['required', 'string', 'unique:users,username,'.$user->id];
        if ($request->input('username') !== $user->username) {
            $usernameRules = array_merge($usernameRules, ['min:3', 'max:30', 'regex:/^[a-z0-9]+$/']);
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:30',
            'username' => $usernameRules,
            'bio' => 'nullable|string|max:500',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'remove_profile_image' => 'nullable|boolean',
            'show_liked_posts' => 'nullable|boolean',
        ]);

        $profileImageUrl = $user->profile_image;

        // Helper: strip the stored image off disk (shared by replace + remove).
        $deleteOldImage = function () use ($user) {
            if ($user->profile_image) {
                $oldPath = str_replace('/storage/', '', parse_url($user->profile_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }
        };

        if ($request->hasFile('profile_image')) {
            $deleteOldImage();
            $path = $request->file('profile_image')->store('profiles', 'public');
            $profileImageUrl = Storage::url($path);
        } elseif ($request->boolean('remove_profile_image')) {
            $deleteOldImage();
            $profileImageUrl = null;
        }

        $user->update([
            'display_name' => $validated['display_name'],
            'username' => $validated['username'],
            'bio' => $validated['bio'] ?? null,
            'profile_image' => $profileImageUrl,
        ]);

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['show_liked_posts' => $request->boolean('show_liked_posts')]
        );

        return back()->with('success', 'Profile updated.');
    }

    // ── Live username availability (AJAX, authed) ────────
    // Mirrors Auth\UsernameController::available(), but ignores the current
    // user's own handle so editing without changing it still reads as free.
    public function checkUsername(Request $request)
    {
        $username = strtolower((string) $request->query('username', ''));
        $valid = (bool) preg_match('/^[a-z0-9]{3,30}$/', $username);

        $taken = $valid && User::withTrashed()
            ->where('username', $username)
            ->where('id', '!=', $request->user()->id)
            ->exists();

        return response()->json([
            'valid' => $valid,
            'available' => $valid && ! $taken,
        ]);
    }

    // ── Save privacy preferences (AJAX) ──────────────────
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'show_liked_posts' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['show_liked_posts' => $validated['show_liked_posts']]
        );

        return response()->json(['show_liked_posts' => $validated['show_liked_posts']]);
    }

    // ── Delete account ───────────────────────────────────
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->google_id) {
            $request->validateWithBag('userDeletion', [
                'confirm_email' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                    if (strtolower($value) !== strtolower($user->email)) {
                        $fail('The email address does not match your account email.');
                    }
                }],
            ]);
        } else {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        // Set deletion schedule (1 month grace period)
        $user->update(['deletion_scheduled_at' => now()->addMonth()]);

        auth()->logout();
        $user->delete(); // Soft delete

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('feed.index')->with('success', 'Your account has been scheduled for deletion. You have 1 month to restore it by logging back in.');
    }
}
