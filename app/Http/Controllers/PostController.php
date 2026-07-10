<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Rules\NoProfanity;
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

        $query = Post::with($with)
            ->withCount(['likes', 'comments', 'bookmarks'])
            ->whereHas('user', fn ($q) => $q->whereNull('users.deleted_at'));

        $profileUsers = collect();

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

            $profileUsers = User::withCount([
                'posts',
                'followers' => fn ($q) => $q->where('follows.status', 'accepted'),
                'following' => fn ($q) => $q->where('follows.status', 'accepted'),
            ])
                ->where(function ($q) use ($escapedSearch) {
                    $q->where('username', 'like', "%{$escapedSearch}%")
                        ->orWhere('display_name', 'like', "%{$escapedSearch}%");
                })
                ->orderByRaw('CASE WHEN username = ? THEN 0 WHEN display_name = ? THEN 1 ELSE 2 END', [$search, $search])
                ->limit(10)
                ->get();
        }

        // 2. Tag filter
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->input('tag'));
            });
        }

        // 3. Sort order — driven by the `sort` query parameter.
        $sort = in_array($request->input('sort'), ['for_you', 'recent', 'popular'])
            ? $request->input('sort')
            : 'for_you';

        if ($sort === 'recent') {
            $query->orderByDesc('posts.created_at');
        } elseif ($sort === 'popular') {
            $query->orderByRaw('(likes_count + 2 * comments_count + 1.5 * bookmarks_count) DESC')
                ->orderByDesc('posts.created_at');
        } else {
            // "For You" — recency + engagement + follow boost + per-session jitter.
            $followedIds = auth()->check()
                ? auth()->user()->following()->wherePivot('status', 'accepted')->pluck('users.id')->all()
                : [];

            $seed = (int) (sprintf('%u', crc32($request->session()->getId().now()->toDateString())) % 100000);

            $query->forYouRanked($followedIds, $seed, auth()->id());
        }

        $posts = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('feed._posts', compact('posts'))->render(),
                'user_card_html' => $profileUsers->isNotEmpty()
                    ? $profileUsers->map(fn ($u) => view('components.user-card', ['user' => $u])->render())->implode('')
                    : '',
                'next_page_url' => $posts->nextPageUrl(),
            ]);
        }

        $tags = Tag::all();

        return view('feed.index', compact('posts', 'tags', 'profileUsers', 'sort'));
    }

    public function create()
    {
        $tags = Tag::all();

        return view('feed.create', compact('tags'));
    }

    public function store(Request $request)
    {
        $contentPresence = ($request->hasFile('media') || $request->hasFile('files'))
            ? 'nullable'
            : 'required';

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255', new NoProfanity],
            'content' => [$contentPresence, 'string', 'max:5000', new NoProfanity],
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,txt|max:20480',
        ]);

        $post = $request->user()->posts()->create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
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
            'bookmarks' => fn ($q) => $q->when(auth()->id(), fn ($q) => $q->where('user_id', auth()->id())),
            'comments' => fn ($q) => $q->whereNull('parent_id')
                ->with(['user', 'replies.user', 'likes'])
                ->latest(),
        ]);

        $post->loadCount(['likes', 'comments', 'bookmarks']);

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

        $contentPresence = ($request->hasFile('media') || $request->hasFile('files'))
            ? 'nullable'
            : 'required';

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255', new NoProfanity],
            'content' => [$contentPresence, 'string', 'max:5000', new NoProfanity],
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:20480',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,txt|max:20480',
            'remove_media' => 'nullable|array',
            'remove_media.*' => 'exists:post_media,id',
        ]);

        $post->update([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
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

        $actorId = auth()->id();
        $isStaff = $actorId !== $post->user_id;

        $post->delete();

        if ($isStaff) {
            ActivityLog::create([
                'user_id' => $actorId,
                'action' => 'content_removed',
                'subject_type' => 'Post',
                'subject_id' => $post->id,
                'properties' => [
                    'username' => $post->user?->username,
                    'reason' => 'Direct removal by staff',
                ],
                'created_at' => now(),
            ]);
        }

        return redirect()->route('feed.index')
            ->with('success', 'Post deleted.');
    }
}
