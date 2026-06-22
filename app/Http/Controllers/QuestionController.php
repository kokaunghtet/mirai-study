<?php

namespace App\Http\Controllers;

use App\Models\ExamCategory;
use App\Models\ExamLevel;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function manage(Request $request)
    {
        $questions = Question::with(['category', 'level'])
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', $request->category)))
            ->when($request->filled('level'), fn ($q) => $q->whereHas('level', fn ($l) => $l->where('code', $request->level)))
            ->when($request->filled('section'), fn ($q) => $q->where('section', $request->section))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = ExamCategory::with('levels:id,category_id,code,name')->orderBy('name')->get(['id', 'name']);

        $sections = [];
        foreach (config('quiz.catalog') as $cat) {
            foreach ($cat['levels'] as $lvl) {
                $sections += $lvl['sections'];
            }
        }

        $counts = [
            'category' => Question::selectRaw('category_id, COUNT(*) c')->groupBy('category_id')->pluck('c', 'category_id'),
            'level' => Question::selectRaw('level_id, COUNT(*) c')->groupBy('level_id')->pluck('c', 'level_id'),
            'section' => Question::whereNotNull('section')->selectRaw('section, COUNT(*) c')->groupBy('section')->pluck('c', 'section'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.questions._list', compact('questions', 'categories', 'sections', 'counts'))->render(),
            ]);
        }

        return view('admin.questions.index', compact('questions', 'categories', 'sections', 'counts'));
    }

    public function create()
    {
        $categories = $this->buildCategoryTree();

        return view('admin.questions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $question = Question::create($this->validatedQuestion($request));

        $question->revisions()->create([
            'editor_id' => Auth::id(),
            'action'    => 'created',
        ]);

        return redirect()->route('admin.questions')->with('success', 'Question added.');
    }

    public function edit(Question $question)
    {
        $categories = $this->buildCategoryTree();

        return view('admin.questions.edit', compact('categories', 'question'));
    }

    public function update(Request $request, Question $question)
    {
        $question->update($this->validatedQuestion($request));

        $question->revisions()->create([
            'editor_id' => Auth::id(),
            'action'    => 'edited',
        ]);

        return redirect()->route('admin.questions')->with('success', 'Question updated.');
    }

    public function history(Question $question)
    {
        $revisions = $question->revisions()->with('editor:id,display_name')->latest()->get();
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

    public function destroy(Question $question)
    {
        $question->delete();

        return redirect()->route('admin.questions')->with('success', 'Question deleted.');
    }

    private function validatedQuestion(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:exam_categories,id'],
            'level_id' => ['required', Rule::exists('exam_levels', 'id')->where('category_id', $request->category_id)],
            'section' => ['nullable', 'string', 'max:255'],
            'text' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'answer' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'explanation' => ['nullable', 'string'],
        ]);

        $cat = ExamCategory::find($data['category_id']);
        $lvl = ExamLevel::find($data['level_id']);
        $valid = array_keys(config("quiz.catalog.{$cat->name}.levels.{$lvl->code}.sections", []));

        if ($valid) {
            $request->validate(['section' => ['required', Rule::in($valid)]]);
        } else {
            $data['section'] = null;
        }

        return $data;
    }

    private function buildCategoryTree()
    {
        $catalog = config('quiz.catalog');

        return ExamCategory::with('levels:id,category_id,code,name')
            ->orderBy('name')->get(['id', 'name'])
            ->each(fn ($cat) => $cat->levels->each(function ($lvl) use ($catalog, $cat) {
                $lvl->sections = $catalog[$cat->name]['levels'][$lvl->code]['sections'] ?? [];
            }));
    }
}
