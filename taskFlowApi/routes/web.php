<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaticsController;

Route::get('/', function () {
    abort(404);
    //    return view('welcome');
});

Route::get('/deduplication_comment_statistics', [StaticsController::class, 'deduplicationCommentStatistics']);
