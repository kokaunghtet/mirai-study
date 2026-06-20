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
    public function manage()
    {
        $papers = ExamPaper::with(['category', 'level'])
            ->withCount('downloads')
            ->latest()
            ->paginate(20);

        return view('admin.papers.index', compact('papers'));
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

        ExamPaper::create([
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

        return redirect()->route('admin.papers')->with('success', 'Paper uploaded.');
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
