<?php

use Illuminate\Support\Facades\Route;
use SatuForm\FormBuilder\Http\Controllers\FormBuilderApiController;

Route::middleware('web')->group(function () {
    $prefix = trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/');
    $apiPrefix = trim((string) config('formbuilder.api_prefix', $prefix.'/api'), '/');

    Route::view('/ais-v4/public/index.php/', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix, 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/login', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/forms', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/forms/fill', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/track', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/admin', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/'.$prefix.'/my-submissions', 'formbuilder::formbuilder');
    Route::view('/ais-v4/public/index.php/dashboard', 'formbuilder::formbuilder')->name('dashboard');

    Route::prefix($apiPrefix)->group(function () {
        Route::get('/bootstrap', [FormBuilderApiController::class, 'bootstrap']);
        Route::get('/submissions/{id}', [FormBuilderApiController::class, 'showSubmission']);
        Route::post('/submissions', [FormBuilderApiController::class, 'storeSubmission']);
        Route::post('/submissions/{id}/review', [FormBuilderApiController::class, 'reviewSubmission']);
        Route::post('/templates', [FormBuilderApiController::class, 'saveTemplate']);
        Route::post('/templates/{id}/toggle-publish', [FormBuilderApiController::class, 'toggleTemplatePublish']);
        Route::delete('/templates/{id}', [FormBuilderApiController::class, 'deleteTemplate']);
        Route::post('/users', [FormBuilderApiController::class, 'saveUser']);
        Route::delete('/users/{id}', [FormBuilderApiController::class, 'deleteUser']);
    });
});
