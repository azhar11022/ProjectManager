<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProjectController;



Route::post('/login',[UserController::class, 'login']);
Route::post('register',[UserController::class, 'register']);

Route::middleware('member.token')->group(function () {
    // projects routes
    Route::get('/projects',[ProjectController::class, 'index']);
    Route::post('/projects',[ProjectController::class, 'store']);
    Route::get('/projects/{id}',[ProjectController::class, 'show']);
    Route::put('/projects/{id}',[ProjectController::class, 'update']);
    Route::delete('/projects/{id}',[ProjectController::class, 'destroy']);

    // members routes
    Route::get('/members',[MemberController::class, 'index']);
    Route::post('/members',[MemberController::class, 'store']);
    Route::get('/members/{id}',[MemberController::class, 'show']);
    Route::put('/members/{id}',[MemberController::class, 'update']);
    Route::delete('/members/{id}',[MemberController::class, 'destroy']);

    // tasks routes
    Route::get('/projects/{pid}/tasks/{id}',[TaskController::class, 'show']);
    Route::post('/projects/{pid}/tasks',[TaskController::class, 'store']);
    Route::put('/projects/{pid}/tasks/{id}',[TaskController::class, 'update']);
    Route::delete('/projects/{pid}/tasks/{id}',[TaskController::class, 'destroy']);

    Route::post('/logout',[UserController::class, 'logout']);
});
