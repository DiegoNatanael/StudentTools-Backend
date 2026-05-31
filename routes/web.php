<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\AdminUserController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [GenerationController::class, 'dashboard'])->name('dashboard');

    // Generations CRUD
    Route::get('/generations', [GenerationController::class, 'index'])->name('generations.index');
    Route::post('/generations/generate', [GenerationController::class, 'generate'])->name('generations.generate');
    Route::get('/generations/{id}', [GenerationController::class, 'show'])->name('generations.show');
    Route::get('/generations/{id}/edit', [GenerationController::class, 'edit'])->name('generations.edit');
    Route::put('/generations/{id}', [GenerationController::class, 'update'])->name('generations.update');
    Route::delete('/generations/{id}', [GenerationController::class, 'destroy'])->name('generations.destroy');

    // Subjects Resource CRUD
    Route::resource('subjects', SubjectController::class);
    
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::put('/admin/users/{id}/quota', [AdminUserController::class, 'updateQuota'])->name('admin.users.quota');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
});