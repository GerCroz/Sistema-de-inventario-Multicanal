<?php

use App\Http\Controllers\SecurityLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('inventario');
});

Route::get('/security-login', function () {
    return view('security-login');
})->middleware('guest')->name('login');

Route::post('/security-login',
    [SecurityLoginController::class, 'login']
)->middleware('guest');
