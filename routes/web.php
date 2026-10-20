<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HierarchyController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/quick-switch/{user}', [AuthController::class, 'quickSwitch'])->name('auth.quick-switch');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Tiketing
    Route::get('/tickets/my', [TicketController::class, 'myTickets'])->name('tickets.my');
    Route::get('/tickets/history', [TicketController::class, 'historyTickets'])->name('tickets.history');
    Route::get('/tickets/inbox', [TicketController::class, 'inbox'])->name('tickets.inbox');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/preview', [TicketController::class, 'previewAttachment'])->name('tickets.preview');
    Route::get('/tickets/{ticket}/attachment', [TicketController::class, 'downloadAttachment'])->name('tickets.attachment');
    Route::get('/categories/{category}/template/preview', [TicketController::class, 'previewCategoryTemplate'])->name('categories.template.preview');
    Route::get('/categories/{category}/template/download', [TicketController::class, 'downloadCategoryTemplate'])->name('categories.template.download');
    Route::post('/tickets/{ticket}/problems', [TicketController::class, 'storeProblems'])->name('tickets.problems.store');
    Route::delete('/tickets/{ticket}/problems/{problem}', [TicketController::class, 'destroyProblem'])->name('tickets.problems.destroy');
    Route::post('/tickets/{ticket}/problems/{problem}/toggle', [TicketController::class, 'toggleProblem'])->name('tickets.problems.toggle');
    Route::post('/tickets/{ticket}/progress', [TicketController::class, 'updateProgress'])->name('tickets.progress');
    Route::post('/tickets/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('tickets.priority');
    Route::post('/tickets/{ticket}/action', [TicketController::class, 'handleAction'])->name('tickets.action');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');

    // Modul Manajemen Superuser
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.users.students'))->name('index');

        Route::get('/hierarchy', [HierarchyController::class, 'index'])->name('hierarchy');
        Route::post('/hierarchy/roles', [HierarchyController::class, 'storeRole'])->name('hierarchy.store');
        Route::get('/hierarchy/roles/{role}/staff', [HierarchyController::class, 'roleStaff'])->name('hierarchy.staff');
        Route::put('/hierarchy/roles/{role}/staff', [HierarchyController::class, 'syncStaff'])->name('hierarchy.staff.sync');
        Route::put('/hierarchy/roles/{role}', [HierarchyController::class, 'updateHierarchy'])->name('hierarchy.update');
        Route::delete('/hierarchy/roles/{role}', [HierarchyController::class, 'destroyRole'])->name('hierarchy.destroy');

        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::post('/menus/matrix', [MenuController::class, 'syncMatrix'])->name('menus.matrix');
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Manajemen Pengguna dengan Sub-Sidebar: Mahasiswa & Pengajar (Dosen, Kaprodi, Dekan)
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/users/students', [UserController::class, 'students'])->name('users.students');
        Route::get('/users/lecturers', [UserController::class, 'lecturers'])->name('users.lecturers');
        Route::post('/users/sync-siakad', [UserController::class, 'syncFromSiakad'])->name('users.sync-siakad');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
