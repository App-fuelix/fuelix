<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/fuel-card', function () {
    return view('fuel-card');
});

Route::get('/history', function () {
    return view('history');
});

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/reset-password', function () {
    return view('reset-password');
})->name('password.reset.form');

Route::get('/test-email', function () {
    Mail::raw('Test email from Fuelix - Mailtrap is working!', function ($message) {
        $message->to('test@example.com')
                ->subject('Mailtrap Test - Success');
    });
    
    return 'Test email sent! Check your Mailtrap inbox.';
});
