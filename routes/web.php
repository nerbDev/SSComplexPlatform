<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merge these into your existing routes/web.php — don't overwrite the file
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/account', [AuthController::class, 'show'])->name('account.show');
Route::post('/account/login', [AuthController::class, 'login'])->name('account.login');
Route::post('/account/register', [AuthController::class, 'register'])->name('account.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');