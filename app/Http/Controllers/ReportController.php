<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment,user'],
            'target_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('target_type');
                    $table = match ($type) {
                        'post' => 'posts',
                        'comment' => 'comments',
                        'user' => 'users',
                        default => null,
                    };

                    if (! $table || ! DB::table($table)->where('id', $value)->exists()) {
                        $fail('The reported content no longer exists.');
                    }
                },
            ],
            'category' => ['required', 'in:spam,harassment,misinformation,inappropriate,other'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Block self-reports
        if ($data['target_type'] === 'user' && (int) $data['target_id'] === auth()->id()) {
            return response()->json(['error' => 'self'], 422);
        }

        // Block reporting admin accounts
        if ($data['target_type'] === 'user') {
            $target = User::find($data['target_id']);
            if ($target && $target->isAdmin()) {
                return response()->json(['error' => 'admin'], 422);
            }
        }

        // Block reporting admin content (posts/comments)
        if ($data['target_type'] === 'post') {
            $target = Post::withTrashed()->find($data['target_id']);
            if ($target && $target->user && $target->user->isAdmin()) {
                return response()->json(['error' => 'admin'], 422);
            }
        } elseif ($data['target_type'] === 'comment') {
            $target = Comment::withTrashed()->find($data['target_id']);
            if ($target && $target->user && $target->user->isAdmin()) {
                return response()->json(['error' => 'admin'], 422);
            }
        }

        $exists = Report::where('reporter_id', auth()->id())
            ->where('target_type', $data['target_type'])
            ->where('target_id', $data['target_id'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'duplicate'], 409);
        }

        // Flag if target is a moderator (so only admins can see these reports)
        $modReport = false;
        if ($data['target_type'] === 'user') {
            $modReport = isset($target) && $target->isModerator();
        } elseif ($data['target_type'] === 'post') {
            $modReport = isset($target) && $target->user && $target->user->isModerator();
        } elseif ($data['target_type'] === 'comment') {
            $modReport = isset($target) && $target->user && $target->user->isModerator();
        }

        Report::create(array_merge($data, [
            'reporter_id' => auth()->id(),
            'mod_report' => $modReport,
        ]));

        Cache::forget('admin_stats');

        return response()->json(['ok' => true]);
    }
}
