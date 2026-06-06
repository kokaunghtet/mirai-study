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

        $preferences  = $user->preferences;
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;
        $isFollowing  = false;

        if (auth()->check() && !$isOwnProfile) {
            $isFollowing = auth()->user()
                ->following()
                ->where('following_id', $user->id)
                ->exists();
        }

        // Liked tab visibility
        $showLikedTab = $isOwnProfile || ($preferences?->show_liked_posts ?? true);

        // Active tab — fall back if liked tab is hidden
        $tab = $request->get('tab', 'posts');
        if ($tab === 'liked' && !$showLikedTab) {
            $tab = 'posts';
        }

        // Constrained eager loads for auth users
        $with = ['user', 'tags', 'media'];
        if (auth()->check()) {
            $userId        = auth()->id();
            $with['bookmarks'] = fn($q) => $q->where('user_id', $userId);
            $with['likes']     = fn($q) => $q->where('user_id', $userId);
        }

        if ($tab === 'posts') {
            $posts = Post::where('user_id', $user->id)
                ->with($with)
                ->withCount(['likes', 'comments'])
                ->latest()
                ->paginate(10);
        } else {
            $posts = $user->likedPosts()
                ->with($with)
                ->withCount(['likes', 'comments'])
                ->latest('post_likes.created_at')
                ->paginate(10);
        }

        if ($request->ajax()) {
            return response()->json([
                'html'          => view('profile._posts', compact('posts'))->render(),
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

        return view('profile.followers', compact('user', 'followers'));
    }

    // ── Following list ───────────────────────────────────
    public function following(User $user)
    {
        $user->loadCount(['posts', 'followers', 'following']);

        $following = $user->following()->paginate(20);

        return view('profile.following', compact('user', 'following'));
    }

    // ── Edit profile settings (private) ─────────────────
    public function edit(Request $request)
    {
        $user        = $request->user();
        $preferences = $user->preferences ?? UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['theme_mode' => 'light', 'accent_color' => 'venom', 'show_liked_posts' => true]
        );

        return view('profile.edit', compact('user', 'preferences'));
    }

    // ── Save profile settings ────────────────────────────
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name'     => 'required|string|max:255',
            'username'         => 'required|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'bio'              => 'nullable|string|max:500',
            'profile_image'    => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'show_liked_posts' => 'nullable|boolean',
        ]);

        $profileImageUrl = $user->profile_image;

        if ($request->hasFile('profile_image')) {
            // Delete old image if it exists
            if ($user->profile_image) {
                $oldPath = str_replace('/storage/', '', parse_url($user->profile_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path            = $request->file('profile_image')->store('profiles', 'public');
            $profileImageUrl = Storage::url($path);
        }

        $user->update([
            'display_name'  => $validated['display_name'],
            'username'      => $validated['username'],
            'bio'           => $validated['bio'] ?? null,
            'profile_image' => $profileImageUrl,
        ]);

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['show_liked_posts' => $request->boolean('show_liked_posts')]
        );

        return back()->with('success', 'Profile updated.');
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
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        auth()->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('feed.index');
    }
}