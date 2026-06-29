<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppealController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentLikeController;
use App\Http\Controllers\ExamCategoryController;
use App\Http\Controllers\ExamPaperController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Public routes — guests and auth users
// -------------------------------------------------------

Route::get('/', fn () => redirect()->route('feed.index'));

// Feed
Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

// Exam section (browse only)
Route::get('/exams', [ExamCategoryController::class, 'index'])->name('exams.index');
Route::get('/exams/{category}', [ExamCategoryController::class, 'show'])->name('exams.show');
Route::get('/exams/{category}/{level}', [ExamPaperController::class, 'index'])->name('exams.papers');

// Questions (browse only)
Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');

// Timer (guest: browser only)
Route::get('/timer', [TimerController::class, 'index'])->name('timer.index');

// User profiles (public)
Route::get('/users/{user:username}', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/users/{user:username}/followers', [ProfileController::class, 'followers'])->name('profile.followers');
Route::get('/users/{user:username}/following', [ProfileController::class, 'following'])->name('profile.following');

// -------------------------------------------------------
// Authenticated routes — must be logged in
// -------------------------------------------------------

// Ban screen — accessible while authenticated (banned users keep their session for appeal access)
Route::get('/banned', fn () => redirect()->route('appeal.create'))->middleware('auth')->name('banned');

// Appeal form — accessible to authenticated banned/suspended users only
Route::middleware('auth')->group(function () {
    Route::get('/appeal', [AppealController::class, 'create'])->name('appeal.create');
    Route::post('/appeal', [AppealController::class, 'store'])->name('appeal.store');
});

// Login-page appeal flow — no auth required (banned users are not logged in)
Route::post('/appeal/guest', [AppealController::class, 'storeGuest'])->name('appeal.store.guest')->middleware('throttle:3,60');
Route::post('/login/clear-ban', function () {
    session()->forget('ban_appeal');

    return redirect()->route('login');
})->name('login.clear-ban');

Route::middleware(['auth', 'not-banned'])->group(function () {

    // --- Profile ---
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/username-available', [ProfileController::class, 'checkUsername'])->name('profile.username-available');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Settings ---
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::patch('/settings/theme-mode', [SettingsController::class, 'updateThemeMode'])->name('settings.theme-mode');
    Route::patch('/settings/two-factor', [SettingsController::class, 'updateTwoFactor'])->name('settings.two-factor');

    // --- Posts ---
    // IMPORTANT: static routes (/posts/create) must come before wildcard (/posts/{post})
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    // --- Comments ---
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // --- Likes ---
    Route::post('/posts/{post}/like', [PostLikeController::class, 'toggle'])->name('posts.like');
    Route::post('/comments/{comment}/like', [CommentLikeController::class, 'toggle'])->name('comments.like');

    // --- Bookmarks ---
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'toggle'])->name('posts.bookmark');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');

    // --- Follows ---
    Route::post('/users/{user}/follow', [FollowController::class, 'toggle'])->name('users.follow');
    Route::post('/users/{user}/remove-follower', [FollowController::class, 'removeFollower'])->name('users.remove-follower');

    // --- Exam downloads ---
    Route::get('/exams/papers/{paper}/view', [ExamPaperController::class, 'view'])->name('exams.view');
    Route::get('/exams/papers/{paper}/download', [ExamPaperController::class, 'download'])->name('exams.download');

    // --- Quiz ---
    // IMPORTANT: static routes (/quiz/start, /quiz/history) must come before the /quiz/{attempt} wildcards
    Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
    Route::post('/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
    Route::get('/quiz/history', [QuizController::class, 'history'])->name('quiz.history');
    Route::get('/quiz/{attempt}/result', [QuizController::class, 'result'])->name('quiz.result');
    Route::get('/quiz/{attempt}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{attempt}/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::delete('/quiz/{attempt}', [QuizController::class, 'abort'])->name('quiz.abort');

    // --- Timer (auth: stores sessions + settings) ---
    Route::post('/timer/sessions', [TimerController::class, 'store'])->name('timer.store');
    Route::patch('/timer/settings', [TimerController::class, 'updateSettings'])->name('timer.settings');

    // --- Reports ---
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store')->middleware('throttle:10,1');

    // --- Notifications ---
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

});

// -------------------------------------------------------
// Public wildcard routes — MUST come after all static /posts/* routes
// -------------------------------------------------------
// Comments partial for the feed drawer (loaded via AJAX) — public, like the show page
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');
Route::get('/posts/{post}/history', [PostController::class, 'history'])->name('posts.history');

Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// -------------------------------------------------------
// Admin routes — must be admin role
// -------------------------------------------------------

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.role');
    Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.status');
    // Appeals — admin only
    Route::get('/appeals', [AdminController::class, 'appeals'])->name('appeals');
    Route::patch('/appeals/{appeal}', [AdminController::class, 'updateAppeal'])->name('appeals.update');

    // Analytics
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [AdminController::class, 'analyticsData'])->name('analytics.data');

    // Exam paper management — admin only
    Route::get('/papers/create', [ExamPaperController::class, 'create'])->name('papers.create');
    Route::post('/papers', [ExamPaperController::class, 'store'])->name('papers.store');
    Route::delete('/papers/{paper}', [ExamPaperController::class, 'destroy'])->name('papers.destroy');

    // Quiz question management — admin only
    Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
});

// Reports + content moderation + direct mod actions — admin or moderator
Route::middleware(['auth', 'admin-or-mod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::patch('/reports/{report}', [AdminController::class, 'updateReport'])->name('reports.update');
    Route::post('/users/{user}/ban', [AdminController::class, 'banUserDirect'])->name('users.ban');
    Route::get('/mod-actions', [AdminController::class, 'modActions'])->name('mod-actions');
});

// Exam papers + questions — admin or moderator (index, edit, update, history)
Route::middleware(['auth', 'admin-or-mod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/papers', [ExamPaperController::class, 'manage'])->name('papers');
    Route::get('/papers/{paper}/edit', [ExamPaperController::class, 'edit'])->name('papers.edit');
    Route::put('/papers/{paper}', [ExamPaperController::class, 'update'])->name('papers.update');
    Route::get('/papers/{paper}/history', [ExamPaperController::class, 'history'])->name('papers.history');

    Route::get('/questions', [QuestionController::class, 'manage'])->name('questions');
    Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::get('/questions/{question}/history', [QuestionController::class, 'history'])->name('questions.history');
});

// Breeze auth routes
require __DIR__.'/auth.php';
