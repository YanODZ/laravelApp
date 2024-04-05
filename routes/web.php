<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;

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

Route::middleware('jwt')->group(function () {
    //INICIO
    Route::get('/welcome', [Controller::class, 'showWelcome'])->name('welcome');
    Route::get('/welcomeSigned', [Controller::class, 'goToWelcome'])->name('welcomeSigned');
    //Route::resource('productos', ProductoController::class);

    //PRODUCTOS ROUTES
    Route::resource('productos', ProductoController::class)->except(['index'])->middleware('checkRole');
    Route::get('productos', [ProductoController::class, 'index'])->name('productos');
    Route::get('productosSigned', [ProductoController::class, 'goToIndex'])->name('productosSigned');
    Route::get('create', [ProductoController::class, 'create'])->name('create')->middleware('checkRole');
    Route::get('createSigned', [ProductoController::class, 'goToCreate'])->name('createSigned')->middleware('checkRole');
    Route::get('edit/{producto}', [ProductoController::class, 'edit'])->name('edit')->middleware('checkRole');
    Route::get('editSigned/{producto}', [ProductoController::class, 'goToEdit'])->name('editSigned')->middleware('checkRole');

    //ADMIN ROUTES
    Route::resource('usuarios', UserController::class)->middleware('checkRoleAdmin','ipRestriction');
    Route::get('usuarios', [UserController::class, 'index'])->name('usuarios')->middleware('checkRoleAdmin','ipRestriction');
    Route::get('usuariosSigned', [UserController::class, 'goToIndex'])->name('usuariosSigned')->middleware('checkRoleAdmin','ipRestriction');
    Route::get('createUser', [UserController::class, 'create'])->name('createUser')->middleware('checkRoleAdmin','ipRestriction');
    Route::get('createUserSigned', [UserController::class, 'goToCreate'])->name('createUserSigned')->middleware('checkRoleAdmin','ipRestriction');
    Route::get('editUser/{user}', [UserController::class, 'edit'])->name('editUser')->middleware('checkRoleAdmin','ipRestriction');
    Route::get('editUserSigned/{user}', [UserController::class, 'goToEdit'])->name('editUserSigned')->middleware('checkRoleAdmin','ipRestriction');
});


Route::get('/', function () {
    return redirect()->route('/');
});

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');

Route::post('/', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::get('/error/invalid-signature', function () {
    return 'Error: La firma no es válida';
})->name('error.invalid_signature');