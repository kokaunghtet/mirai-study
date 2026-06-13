<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\CommentLikeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ExamCategoryController;
use App\Http\Controllers\ExamPaperController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Public routes — guests and auth users
// -------------------------------------------------------

Route::get('/', fn() => redirect()->route('feed.index'));

// Feed
Route::get('/feed', [PostController::class, 'index'])->name('feed.index');

// User profiles (public)
Route::get('/users/{user:username}', [ProfileController::class, 'show'])->name('profile.show');

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

Route::middleware('auth')->group(function () {

    // --- Profile ---
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
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
    Route::get('/exams/papers/{paper}/download', [ExamPaperController::class, 'download'])->name('exams.download');

    // --- Quiz ---
    Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
    Route::post('/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
    Route::get('/quiz/{attempt}/result', [QuizController::class, 'result'])->name('quiz.result');
    Route::get('/quiz/{attempt}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{attempt}/submit', [QuizController::class, 'submit'])->name('quiz.submit');

    // --- Timer (auth: stores sessions + settings) ---
    Route::post('/timer/sessions', [TimerController::class, 'store'])->name('timer.store');
    Route::patch('/timer/settings', [TimerController::class, 'updateSettings'])->name('timer.settings');

    // --- Notifications ---
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

});

// -------------------------------------------------------
// Public wildcard routes — MUST come after all static /posts/* routes
// -------------------------------------------------------
// Comments partial for the feed drawer (loaded via AJAX) — public, like the show page
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');

Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// -------------------------------------------------------
// Admin routes — must be admin role
// -------------------------------------------------------

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.status');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::patch('/reports/{report}', [AdminController::class, 'updateReport'])->name('reports.update');
});

// Breeze auth routes
require __DIR__ . '/auth.php';