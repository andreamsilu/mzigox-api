<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', RestrictedDocsAccess::class])->group(function (): void {
    Route::view('/docs/swagger', 'swagger')->name('docs.swagger');
});

Route::redirect('/swagger', '/docs/swagger', 301);
