<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:post,comment'],
            'target_id'   => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    $table = $request->input('target_type') === 'post' ? 'posts' : 'comments';
                    if (! DB::table($table)->where('id', $value)->exists()) {
                        $fail('The reported content no longer exists.');
                    }
                },
            ],
            'category'    => ['required', 'in:spam,harassment,misinformation,inappropriate,other'],
            'reason'      => ['nullable', 'string', 'max:500'],
        ]);

        $exists = Report::where('reporter_id', auth()->id())
            ->where('target_type', $data['target_type'])
            ->where('target_id', $data['target_id'])
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'duplicate'], 409);
        }

        Report::create(array_merge($data, ['reporter_id' => auth()->id()]));

        return response()->json(['ok' => true]);
    }
}
