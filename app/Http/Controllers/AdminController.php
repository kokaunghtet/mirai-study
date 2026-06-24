<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ExamPaper;
use App\Models\Post;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'banned_users' => User::where('status', 'banned')->count(),
            'total_posts' => Post::count(),
            'posts_today' => Post::whereDate('created_at', today())->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'total_papers' => ExamPaper::count(),
            'total_questions' => Question::count(),
        ];

        $recent_users = User::latest()->limit(5)->get();
        $recent_reports = Report::with('reporter')
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        $activityItems = ActivityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_reports', 'activityItems'));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $q = addcslashes($request->search, '%_\\');
            $query->where(function ($q2) use ($q) {
                $q2->where('display_name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.users._list', compact('users'))->render(),
            ]);
        }

        return view('admin.users.index', compact('users'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:active,banned']);

        if ($user->id === auth()->id()) {
            abort(403, 'Cannot change your own status.');
        }

        if ($user->isAdmin()) {
            abort(403, 'Cannot change the status of another admin.');
        }

        $user->update(['status' => $request->status]);

        return response()->json(['status' => $user->status]);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:user,moderator,admin']);

        if ($user->id === auth()->id()) {
            abort(403, 'You cannot change your own role.');
        }

        $oldRole = $user->role;

        DB::transaction(function () use ($user, $request, $oldRole) {
            $user->update(['role' => $request->role]);
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'role_changed',
                'subject_type' => 'User',
                'subject_id' => $user->id,
                'properties' => ['from_role' => $oldRole, 'to_role' => $request->role],
                'created_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', "{$user->display_name}'s role has been updated to {$request->role}.");
    }

    public function reports(Request $request)
    {
        $query = Report::with(['reporter', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('type')) {
            $query->where('target_type', $request->type);
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.reports._list', compact('reports'))->render(),
            ]);
        }

        return view('admin.reports.index', compact('reports'));
    }

    public function updateReport(Request $request, Report $report)
    {
        if ($report->status !== 'pending') {
            abort(422, 'This report has already been processed.');
        }

        $request->validate(['status' => 'required|in:reviewed,dismissed']);

        $report->update([
            'status' => $request->status,
            'reviewed_by' => auth()->id(),
        ]);

        return response()->json(['status' => $report->status]);
    }

    public function analytics()
    {
        return view('admin.analytics.index', ['initialData' => $this->buildAnalyticsData('30d', null, null)]);
    }

    public function analyticsData(Request $request)
    {
        $request->validate([
            'range' => 'sometimes|in:7d,30d,90d,custom',
            'from'  => 'required_if:range,custom|nullable|date|before_or_equal:today',
            'to'    => 'required_if:range,custom|nullable|date|after_or_equal:from|before_or_equal:today',
        ]);

        $range = $request->input('range', '30d');
        $from = $request->input('from');
        $to = $request->input('to');

        return response()->json($this->buildAnalyticsData($range, $from, $to));
    }

    private function buildAnalyticsData(string $range, ?string $from, ?string $to): array
    {
        // Date boundaries — all UTC (D-06)
        if ($from && $to) {
            $start = Carbon::parse($from, 'UTC')->startOfDay();
            $end = Carbon::parse($to, 'UTC')->endOfDay();
        } else {
            $end = Carbon::now('UTC');
            $start = match ($range) {
                '7d'  => Carbon::now('UTC')->subDays(6)->startOfDay(),
                '90d' => Carbon::now('UTC')->subDays(89)->startOfDay(),
                default => Carbon::now('UTC')->subDays(29)->startOfDay(), // 30d
            };
        }

        // Granularity — weekly if >30 days (D-01 through D-05)
        $useWeekly = ($range === '90d') || ($from && $to && Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 30);

        // Daily queries — SQLite-compatible GROUP BY DATE() (D-05)
        $userRows = User::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $paperRows = ExamPaper::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $questionRows = Question::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $attemptRows = QuizAttempt::selectRaw('DATE(completed_at) as day, COUNT(*) as count')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $passedRows = QuizAttempt::selectRaw('DATE(completed_at) as day, COUNT(*) as count')
            ->whereNotNull('completed_at')
            ->whereRaw('CAST(score AS FLOAT) / NULLIF(total_questions, 0) >= 0.6')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $labels = [];
        $registrations = [];
        $papers = [];
        $questions = [];
        $quizAttempts = [];
        $passRates = [];

        if ($useWeekly) {
            // Weekly bucketing — ISO Monday start (D-05)
            $cursor = $start->copy()->startOfWeek();
            while ($cursor->lte($end)) {
                $bucketEnd = $cursor->copy()->endOfWeek();
                if ($bucketEnd->gt($end)) {
                    $bucketEnd = $end->copy();
                }

                $labels[] = $cursor->copy()->format('M j');

                $uCount = 0;
                $pCount = 0;
                $qCount = 0;
                $aCount = 0;
                $pPass = 0;

                for ($d = $cursor->copy(); $d->lte($bucketEnd); $d->addDay()) {
                    $key = $d->toDateString();
                    $uCount += $userRows->get($key)?->count ?? 0;
                    $pCount += $paperRows->get($key)?->count ?? 0;
                    $qCount += $questionRows->get($key)?->count ?? 0;
                    $aCount += $attemptRows->get($key)?->count ?? 0;
                    $pPass += $passedRows->get($key)?->count ?? 0;
                }

                $registrations[] = $uCount;
                $papers[] = $pCount;
                $questions[] = $qCount;
                $quizAttempts[] = $aCount;
                $passRates[] = $aCount > 0 ? (int) round($pPass / $aCount * 100) : 0;

                $cursor->addWeek();
            }
        } else {
            // Daily iteration
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->copy()->format('M j');
                $registrations[] = $userRows->get($key)?->count ?? 0;
                $papers[] = $paperRows->get($key)?->count ?? 0;
                $questions[] = $questionRows->get($key)?->count ?? 0;
                $quizAttempts[] = $attemptRows->get($key)?->count ?? 0;
                $passRates[] = ($attemptRows->get($key)?->count ?? 0) > 0
                    ? (int) round(($passedRows->get($key)?->count ?? 0) / ($attemptRows->get($key)?->count) * 100)
                    : 0;
                $cursor->addDay();
            }
        }

        // KPIs — period-filtered totals
        $kpisNewUsers = User::whereBetween('created_at', [$start, $end])->count();
        $kpisNewPapers = ExamPaper::whereBetween('created_at', [$start, $end])->count();
        $kpisAttempts = QuizAttempt::whereNotNull('completed_at')->whereBetween('completed_at', [$start, $end])->count();
        $kpisPassed = QuizAttempt::whereNotNull('completed_at')->whereBetween('completed_at', [$start, $end])
            ->whereRaw('CAST(score AS FLOAT) / NULLIF(total_questions, 0) >= 0.6')->count();

        $kpis = [
            'totalUsers' => User::count(),
            'totalPapers' => ExamPaper::count(),
            'quizAttempts' => $kpisAttempts,
            'passRate' => $kpisAttempts > 0 ? (int) round($kpisPassed / $kpisAttempts * 100) : 0,
            'newUsersThisPeriod' => $kpisNewUsers,
            'newPapersThisPeriod' => $kpisNewPapers,
        ];

        // Performance table (ANLT-03) — by category + level
        $perfRows = QuizAttempt::with(['category', 'level'])
            ->selectRaw('category_id, level_id, COUNT(*) as attempts, AVG(CAST(score AS FLOAT) / NULLIF(total_questions, 0) * 100) as avg_pct, SUM(CASE WHEN CAST(score AS FLOAT) / NULLIF(total_questions, 0) >= 0.6 THEN 1 ELSE 0 END) as passed_count')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('category_id', 'level_id')
            ->get();

        $performanceTable = $perfRows->map(fn ($r) => [
            'category' => $r->category?->name ?? '—',
            'level' => $r->level?->code ?? '—',
            'attempts' => $r->attempts,
            'passRate' => $r->attempts > 0 ? (int) round($r->passed_count / $r->attempts * 100) : 0,
            'avgScore' => $r->avg_pct !== null ? (int) round($r->avg_pct) : null,
        ])->values()->toArray();

        return [
            'labels' => $labels,
            'registrations' => $registrations,
            'papers' => $papers,
            'questions' => $questions,
            'quizAttempts' => $quizAttempts,
            'passRates' => $passRates,
            'kpis' => $kpis,
            'performanceTable' => $performanceTable,
        ];
    }
}
