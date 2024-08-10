<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AuthSocialiteController;
use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\BorrowController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::apiResource('category', CategoryController::class);
    Route::get('category-all', [CategoryController::class, 'getAll']);
    Route::get('category-search', [CategoryController::class, 'search']);
    Route::apiResource('book', BookController::class);
    Route::get('search-book', [BookController::class, 'search']);
    Route::get('book-news', [BookController::class, 'bookNews']);
    Route::get('book-all', [BookController::class, 'getAll']);
    Route::get('book-zero', [BookController::class, 'bookZero']);
    Route::get('book-chard', [BookController::class, 'chartIndexBook']);
    Route::get('book-chart', [BookController::class, 'borrowStats']);
    Route::post('book-pdf', [BookController::class, 'generatePdf'])->middleware('auth:api', 'isOwner')->name('book.pdf');
    Route::apiResource('role', RoleController::class);
    Route::post('borrow', [BorrowController::class, 'store'])->middleware('auth:api');
    Route::get('borrow', [BorrowController::class, 'index'])->middleware('auth:api', 'isOwner');
    Route::get('borrow-user', [BorrowController::class, 'getBorrowsByUserId'])->middleware('auth:api');
    Route::post('borrow-pdf', [BorrowController::class, 'generatePdf'])->middleware('auth:api', 'isOwner')->name('borrow.pdf');
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::get('google', [AuthController::class, 'redirectToGoogle']);
        Route::post('google/callback', [AuthController::class, 'handleGoogleCallback']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });
    Route::get('get-user', [AuthController::class, 'getUser'])->middleware('auth:api');
    Route::put('update-user', [AuthController::class, 'updateUser'])->middleware('auth:api');
    Route::post('update-profile', [ProfileController::class, 'store'])->middleware('auth:api');
    Route::get('get-profile', [ProfileController::class, 'index'])->middleware('auth:api');
});

Route::options('{any}', function (Request $request) {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
})->where('any', '.*');


