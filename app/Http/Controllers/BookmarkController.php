<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $bookmarks = $request->user()
            ->bookmarkedPosts()
            ->with(['user', 'tags'])
            ->withCount(['likes', 'comments'])
            ->latest('bookmarks.created_at')
            ->paginate(15);

        return view('bookmarks.index', compact('bookmarks'));
    }

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $existing = $user->bookmarkedPosts()->where('post_id', $post->id)->first();

        if ($existing) {
            $user->bookmarkedPosts()->detach($post->id);
            $bookmarked = false;
        } else {
            $user->bookmarkedPosts()->attach($post->id);
            $bookmarked = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['bookmarked' => $bookmarked]);
        }

        return back();
    }
}