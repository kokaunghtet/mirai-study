<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $posts = $request->user()
            ->bookmarkedPosts()
            ->with([
                'user',
                'tags',
                'media',
                'bookmarks' => fn($q) => $q->where('user_id', $request->user()->id),
            ])
            ->withCount(['likes', 'comments'])
            ->latest('bookmarks.created_at')
            ->paginate(10);

        return view('bookmarks.index', compact('posts'));
    }

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $isBookmarked = $user->bookmarkedPosts()->where('post_id', $post->id)->exists();

        if ($isBookmarked) {
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