<?php

use App\Http\Controllers\CalenderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('calenders', CalenderController::class);

    Route::put('/calenders/{eventId}/resize', [CalenderController::class, 'resizeEvent'])->name('resize-calender');
    Route::get('/refetch-calender', [CalenderController::class, 'refetchEvents'])->name('refetch-calender');
});
