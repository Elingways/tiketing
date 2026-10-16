<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HierarchyController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// Halaman Login & Quick Switch Akun/Role (bisa langsung diklik tanpa mengetik password saat demo)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/quick-switch/{user}', [AuthController::class, 'quickSwitch'])->name('auth.quick-switch');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Beranda Utama SuperApp
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Tiketing Berdasarkan Hirarki Role
    Route::get('/tickets/inbox', [TicketController::class, 'inbox'])->name('tickets.inbox');
    Route::get('/tickets/my', [TicketController::class, 'myTickets'])->name('tickets.my');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/action', [TicketController::class, 'handleAction'])->name('tickets.action');
    Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');

    // Modul Manajemen Superuser (Hirarki Role, Menu Dinamis, Kategori & Workflow, Pengguna)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Manajemen Hirarki & Role Dinamis
        Route::get('/hierarchy', [HierarchyController::class, 'index'])->name('hierarchy');
        Route::post('/hierarchy/roles', [HierarchyController::class, 'storeRole'])->name('hierarchy.store');
        Route::put('/hierarchy/roles/{role}', [HierarchyController::class, 'updateHierarchy'])->name('hierarchy.update');
        Route::delete('/hierarchy/roles/{role}', [HierarchyController::class, 'destroyRole'])->name('hierarchy.destroy');

        // Manajemen Menu Dinamis & Hak Akses Role
        Route::get('/menus', [MenuController::class, 'index'])->name('menus');
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::post('/menus/matrix', [MenuController::class, 'syncMatrix'])->name('menus.matrix');
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');

        // Manajemen Kategori & Konfigurasi Workflow Hirarki
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Manajemen Pengguna & Penempatan Role
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
