<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $posts = $request->user()
            ->bookmarkedPosts()
            ->with([
                'user',
                'tags',
                'media',
                'bookmarks' => fn($q) => $q->where('user_id', $userId),
                'likes'     => fn($q) => $q->where('user_id', $userId),
            ])
            ->withCount(['likes', 'comments'])
            ->latest('bookmarks.created_at')
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'html'          => view('bookmarks._posts', compact('posts'))->render(),
                'next_page_url' => $posts->nextPageUrl(),
            ]);
        }

        return view('bookmarks.index', compact('posts'));
    }

    public function toggle(Request $request, Post $post)
    {
        $user = $request->user();

        $isBookmarked = $user->bookmarkedPosts()
            ->where('post_id', $post->id)
            ->exists();

        if ($isBookmarked) {
            $user->bookmarkedPosts()->detach($post->id);
            $bookmarked = false;
        } else {
            $user->bookmarkedPosts()->attach($post->id);
            $bookmarked = true;
        }
        return response()->json(['bookmarked' => $bookmarked]);
    }
}