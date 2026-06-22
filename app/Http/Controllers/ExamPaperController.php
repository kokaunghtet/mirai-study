<?php

namespace App\Http\Controllers;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\ExamPaper;
use App\Models\PaperDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExamPaperController extends Controller
{
    // -----------------------------------------------------------------
    // Public browse (Phase B)
    // -----------------------------------------------------------------

    /**
     * Papers for one category+level. AJAX → just the list partial; a direct
     * (non-AJAX) hit returns the full browser preselected for deep-linking.
     */
    public function index(Request $request, ExamCategory $category, ExamLevel $level)
    {
        abort_unless($level->category_id === $category->id, 404);

        if ($request->ajax() || $request->wantsJson()) {
            $papers = ExamPaper::where('level_id', $level->id)->orderByDesc('year')->get();

            return response()->json([
                'papers' => $papers->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'year' => $p->year,
                    'session' => $p->session,
                    'part' => $p->part,
                    'doc_type' => $p->doc_type,
                    'view_url' => route('exams.view', $p),
                    'download_url' => route('exams.download', $p),
                ])->values(),
            ]);
        }

        // Direct/deep-link hit → render the folders-first browser.
        return view('exams.index', ['categories' => ExamCategoryController::browseCategories()]);
    }

    // -----------------------------------------------------------------
    // Admin management (Phase A)
    // -----------------------------------------------------------------

    /** List every uploaded paper for admins to manage. */
    public function manage(Request $request)
    {
        $papers = ExamPaper::with(['category', 'level'])
            ->withCount('downloads')
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $request->category)))
            ->when($request->filled('level'), fn ($q) => $q->whereHas('level', fn ($l) => $l->where('code', $request->level)))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->when($request->filled('doc_type'), fn ($q) => $q->where('doc_type', $request->doc_type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = ExamCategory::with('levels:id,category_id,code,name')->orderBy('name')->get(['id', 'name']);
        $years = ExamPaper::distinct()->orderByDesc('year')->pluck('year');

        $counts = [
            'category' => ExamPaper::selectRaw('category_id, COUNT(*) c')->groupBy('category_id')->pluck('c', 'category_id'),
            'level' => ExamPaper::selectRaw('level_id, COUNT(*) c')->whereNotNull('level_id')->groupBy('level_id')->pluck('c', 'level_id'),
            'year' => ExamPaper::selectRaw('year, COUNT(*) c')->groupBy('year')->pluck('c', 'year'),
            'doc_type' => ExamPaper::whereNotNull('doc_type')->selectRaw('doc_type, COUNT(*) c')->groupBy('doc_type')->pluck('c', 'doc_type'),
        ];

        return view('admin.papers.index', compact('papers', 'categories', 'years', 'counts'));
    }

    /** Edit form for paper metadata (PDF is not replaceable here). */
    public function edit(ExamPaper $paper)
    {
        $categories = ExamCategory::with('levels:id,category_id,code,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.papers.edit', compact('categories', 'paper'));
    }

    /** Update paper metadata only — file_url and file_type are left untouched. */
    public function update(Request $request, ExamPaper $paper)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:exam_categories,id'],
            'level_id' => ['required', Rule::exists('exam_levels', 'id')->where('category_id', $request->category_id)],
            'title' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'session' => ['nullable', 'string', 'max:255'],
            'part' => ['nullable', 'string', Rule::in(['AM', 'PM'])],
            'doc_type' => ['nullable', 'string', Rule::in(['question', 'answer', 'combined'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $paper->update([
            'category_id' => $validated['category_id'],
            'level_id' => $validated['level_id'],
            'title' => filled($validated['title']) ? $validated['title'] : $paper->title,
            'year' => $validated['year'],
            'session' => $validated['session'] ?? null,
            'part' => $validated['part'] ?? null,
            'doc_type' => $validated['doc_type'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $paper->revisions()->create([
            'editor_id' => $request->user()->id,
            'action' => 'edited',
        ]);

        return redirect()->route('admin.papers')->with('success', 'Paper updated.');
    }

    /** Upload form. Categories carry their levels for the dependent dropdown. */
    public function create()
    {
        $categories = ExamCategory::with('levels:id,category_id,code,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.papers.create', compact('categories'));
    }

    /** Validate, store the PDF on the public disk, and persist the row. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:exam_categories,id'],
            // Level must exist *and* belong to the chosen category.
            'level_id' => ['required', Rule::exists('exam_levels', 'id')->where('category_id', $request->category_id)],
            // Optional — falls back to the uploaded file name (see below).
            'title' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'session' => ['nullable', 'string', 'max:255'],
            // Derived from the filename (AM/PM sitting; question paper vs answer key).
            'part' => ['nullable', 'string', Rule::in(['AM', 'PM'])],
            'doc_type' => ['nullable', 'string', Rule::in(['question', 'answer', 'combined'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file = $request->file('file');

        // Blank title → derive from the file name: drop extension, turn _/- into
        // spaces, collapse whitespace. e.g. "FE_2023-July.pdf" → "FE 2023 July".
        $title = $validated['title'] ?? null;
        if (blank($title)) {
            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $title = Str::of($base)->replace(['_', '-'], ' ')->squish()->toString();
            $title = $title !== '' ? $title : 'Untitled paper';
        }

        $path = $file->store('exam-papers', 'public');

        $paper = ExamPaper::create([
            'category_id' => $validated['category_id'],
            'level_id' => $validated['level_id'],
            'uploaded_by' => $request->user()->id,
            'title' => $title,
            'year' => $validated['year'],
            'session' => $validated['session'] ?? null,
            'part' => $validated['part'] ?? null,
            'doc_type' => $validated['doc_type'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_url' => $path,
            'file_type' => 'pdf',
        ]);

        $paper->revisions()->create([
            'editor_id' => $request->user()->id,
            'action' => 'uploaded',
        ]);

        return redirect()->route('admin.papers')->with('success', 'Paper uploaded.');
    }

    public function history(ExamPaper $paper)
    {
        $revisions = $paper->revisions()->with('editor:id,display_name')->latest()->get();
        $total = $revisions->count();

        return response()->json(
            $revisions->values()->map(fn ($r, $i) => [
                'id'              => $r->id,
                'is_latest'       => $i === 0,
                'is_initial'      => $i === $total - 1,
                'action'          => $r->action,
                'editor'          => [
                    'display_name' => $r->editor->display_name,
                    'initial'      => strtoupper(substr($r->editor->display_name, 0, 1)),
                ],
                'created_at'      => $r->created_at->diffForHumans(),
                'created_at_full' => $r->created_at->format('M j, Y · g:i A'),
            ])
        );
    }

    /** Delete the stored file then the row. */
    public function destroy(ExamPaper $paper)
    {
        Storage::disk('public')->delete($paper->file_url);
        $paper->delete();

        return redirect()->route('admin.papers')->with('success', 'Paper deleted.');
    }

    // -----------------------------------------------------------------
    // Download (Phase C)
    // -----------------------------------------------------------------

    /** Stream the PDF inline so the browser renders it (no download count). */
    public function view(ExamPaper $paper)
    {
        $name = Str::slug($paper->title).'.'.$paper->file_type;

        // Default disposition for Storage::response() is "inline" → browser viewer.
        return Storage::disk('public')->response($paper->file_url, $name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** Record the download then stream the file. */
    public function download(ExamPaper $paper)
    {
        PaperDownload::create([
            'user_id' => auth()->id(),
            'paper_id' => $paper->id,
        ]);

        $name = Str::slug($paper->title).'.'.$paper->file_type;

        return Storage::disk('public')->download($paper->file_url, $name);
    }
}
