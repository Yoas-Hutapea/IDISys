<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home.index');
})->middleware('auth');

Route::get('/profile', function () {
    return view('account.profile');
})->middleware('auth')->name('profile');
