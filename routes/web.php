<?php

use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard route
Route::get('/dashboard', [UserController::class, 'index'])->name('dashboard');

// User resource routes
Route::resource('users', UserController::class);

// Notification routes
Route::resource('notifications', NotificationsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

// Mark notifications as read routes
Route::post('/notifications/mark-read', [NotificationsController::class, 'markAsRead'])->name('notifications.mark-read');