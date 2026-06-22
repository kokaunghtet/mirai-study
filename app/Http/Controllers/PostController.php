<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class PostController extends Controller
{
    use AuthorizesRequests;

    // Anyone can view the feed
    public function index(Request $request)
    {
        $with = ['user', 'tags', 'media'];

        if (auth()->check()) {
            $userId = auth()->id();
            $with['bookmarks'] = fn ($q) => $q->where('user_id', $userId);
            $with['likes'] = fn ($q) => $q->where('user_id', $userId);
        }

        $query = Post::with($with)->withCount(['likes', 'comments', 'bookmarks']);

        // 1. Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            // Escape LIKE wildcards
            $escapedSearch = addcslashes($search, '%_\\');

            $query->where(function ($q) use ($escapedSearch) {
                $q->where('title', 'like', "%{$escapedSearch}%")
                    ->orWhere('content', 'like', "%{$escapedSearch}%")
                    ->orWhereHas('user', function ($uq) use ($escapedSearch) {
                        $uq->where('display_name', 'like', "%{$escapedSearch}%")
                            ->orWhere('username', 'like', "%{$escapedSearch}%");
                    });
            });
        }

        // 2. Tag filter
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->input('tag'));
            });
        }

        // 3. "For You" ranking — recency + engagement + a boost for authors
        //    the viewer follows, plus a per-session jitter so the feed feels
        //    alive on revisit. The order is byte-stable across the separate
        //    AJAX requests infinite scroll fires (see Post::scopeForYouRanked).
        $followedIds = auth()->check()
            ? auth()->user()->following()->wherePivot('status', 'accepted')->pluck('users.id')->all()
            : [];

        // Seed is derived from the session id + today's date: stable within a
        // scroll session (so paging never duplicates/skips), but rotates daily
        // so the order subtly reshuffles between visits.
        $seed = (int) (sprintf('%u', crc32($request->session()->getId().now()->toDateString())) % 100000);

        // Pass the viewer id so their own freshly-created posts pin to the top of
        // their feed for a short window (see Post::scopeForYouRanked). Null for guests.
        $query->forYouRanked($followedIds, $seed, auth()->id());

        $posts = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('feed._posts', compact('posts'))->render(),
                'next_page_url' => $posts->nextPageUrl(),
            ]);
        }

        $tags = Tag::all();

        return view('feed.index', compact('posts', 'tags'));
    }

    public function create()
    {
        $tags = Tag::all();

        return view('feed.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => ($request->hasFile('media') || $request->hasFile('files'))
                ? 'nullable|string|max:5000'
                : 'required|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,txt|max:20480',
        ]);

        $post = $request->user()->posts()->create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        $post->revisions()->create([
            'editor_id' => $request->user()->id,
            'title' => $post->title,
            'content' => $post->content,
        ]);

        if (! empty($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }

        // Handle media uploads (images/videos)
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $this->storeUpload($file, 'posts/media');

                $post->media()->create([
                    'url' => Storage::url($path),
                    'type' => 'image',
                ]);
            }
        }

        // Handle document/file uploads (PDF, txt, and images)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $this->storeUpload($file, 'posts/files');
                $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

                $post->media()->create([
                    'url' => Storage::url($path),
                    'type' => $isImage ? 'image' : 'document',
                    'filename' => $isImage ? null : $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post created successfully.');
    }

    // Anyone can view a single post
    public function show(Post $post)
    {
        $post->load([
            'user',
            'tags',
            'media',
            'likes',
            'comments' => fn ($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies.user', 'likes'])
                ->latest(),
        ]);

        $post->loadCount(['likes', 'comments']);

        return view('feed.show', compact('post'));
    }

    public function edit(Post $post)
    {
        // Only the post owner can edit
        $this->authorize('update', $post);

        $tags = Tag::all();
        $post->load('tags');

        return view('feed.edit', compact('post', 'tags'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => ($request->hasFile('media') || $request->hasFile('files'))
                ? 'nullable|string|max:5000'
                : 'required|string|max:5000',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,txt|max:20480',
            'remove_media' => 'nullable|array',
            'remove_media.*' => 'exists:post_media,id',
        ]);

        $post->update([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
        ]);

        $post->revisions()->create([
            'editor_id' => $request->user()->id,
            'title' => $post->title,
            'content' => $post->content,
        ]);

        $post->tags()->sync($validated['tags'] ?? []);

        // Remove media the user deleted
        if (! empty($validated['remove_media'])) {
            $post->media()->whereIn('id', $validated['remove_media'])->delete();
        }

        // Add new media
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $this->storeUpload($file, 'posts/media');

                $post->media()->create([
                    'url' => Storage::url($path),
                    'type' => 'image',
                ]);
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $this->storeUpload($file, 'posts/files');

                $post->media()->create([
                    'url' => Storage::url($path),
                    'type' => 'document',
                    'filename' => $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('posts.show', $post)
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Store an uploaded file on the public disk.
     *
     * Raster images are scaled down to a max width of 1920px and re-encoded
     * as WebP (quality 80) to shrink storage. Animated GIFs and non-images
     * are stored untouched. Returns the storage path.
     */
    private function storeUpload(UploadedFile $file, string $dir): string
    {
        $mime = $file->getMimeType();

        // Only re-encode static raster images; pass GIFs (animation) and
        // non-images (PDF, txt, …) through unchanged.
        if (! str_starts_with((string) $mime, 'image/') || $mime === 'image/gif') {
            return $file->store($dir, 'public');
        }

        $encoded = Image::decode($file->getRealPath())
            ->scaleDown(width: 1920)
            ->encode(new WebpEncoder(quality: 80));

        $path = $dir.'/'.Str::random(40).'.webp';
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    public function history(Post $post)
    {
        $revisions = $post->revisions()->with('editor:id,display_name,username')->latest()->get();
        $total = $revisions->count();

        return response()->json(
            $revisions->values()->map(fn ($r, $i) => [
                'id' => $r->id,
                'is_latest' => $i === 0,
                'is_initial' => $i === $total - 1,
                'editor' => [
                    'display_name' => $r->editor->display_name,
                    'initial' => strtoupper(substr($r->editor->display_name, 0, 1)),
                ],
                'title' => $r->title,
                'content_preview' => $r->content ? Str::limit($r->content, 80) : null,
                'created_at' => $r->created_at->diffForHumans(),
                'created_at_full' => $r->created_at->format('M j, Y · g:i A'),
            ])
        );
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete(); // soft delete

        return redirect()->route('feed.index')
            ->with('success', 'Post deleted.');
    }
}
