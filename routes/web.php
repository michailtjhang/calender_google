<?php

use App\Http\Controllers\CalenderController;
use App\Http\Controllers\OauthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('calenders', CalenderController::class);

    Route::get('/sosial-media/google/callback', [OauthController::class, 'callback'])->name('sosial-media.google.callback');

    Route::put('/calenders/{eventId}/resize', [CalenderController::class, 'resizeEvent'])->name('resize-calender');
    Route::get('/refetch-calender', [CalenderController::class, 'refetchEvents'])->name('refetch-calender');
});
