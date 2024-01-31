<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::middleware('signed','jwt')->group(function () {
    Route::get('/welcome', [Controller::class, 'showWelcome'])->name('welcome');
});


Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['signed'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
});

Route::post('/login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::get('/error/invalid-signature', function () {
    return 'Error: La firma no es válida';
})->name('error.invalid_signature');