<?php

use App\Http\Controllers\Api\ContentBriefController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ScriptGenerationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::post('/projects/{project}/content-brief', [ContentBriefController::class, 'store']);
    Route::post('/projects/{project}/script-generations', [ScriptGenerationController::class, 'store']);
});
