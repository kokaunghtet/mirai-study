<?php

namespace App\Http\Controllers;

use App\Models\ExamCategory;

class ExamCategoryController extends Controller
{
    /** Browse landing — first category + its first level preselected. */
    public function index()
    {
        return $this->renderBrowser();
    }

    /** Deep link to a specific category (non-JS / shareable URL). */
    public function show(ExamCategory $category)
    {
        return $this->renderBrowser($category);
    }

    /**
     * Render the two-pane browser. The full category→level tree is handed to
     * Alpine so folders + level tabs need no extra round-trips; only the paper
     * list is fetched on demand (see ExamPaperController::index).
     */
    private function renderBrowser(?ExamCategory $active = null)
    {
        return view('exams.index', ['categories' => self::browseCategories()]);
    }

    /**
     * Category→level tree with paper counts, shared by the browse landing and
     * the deep-link paper route. Counts drive the folder/level cards; the paper
     * list itself is fetched on demand (ExamPaperController::index).
     */
    public static function browseCategories()
    {
        return ExamCategory::withCount('papers')
            ->with(['levels' => fn ($q) => $q->select('id', 'category_id', 'code', 'name')->withCount('papers')])
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
