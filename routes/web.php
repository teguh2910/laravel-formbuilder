<?php

use Illuminate\Support\Facades\Route;
use SatuForm\FormBuilder\Http\Controllers\FormBuilderApiController;
use SatuForm\FormBuilder\Http\Controllers\FormBuilderPageController;

Route::middleware('web')->group(function () {
    $prefix = trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/');
    $apiPrefix = trim((string) config('formbuilder.api_prefix', $prefix.'/api'), '/');

    Route::get('/', [FormBuilderPageController::class, 'landing']);
    Route::get('/'.$prefix, [FormBuilderPageController::class, 'landing']);
    Route::get('/'.$prefix.'/login', [FormBuilderPageController::class, 'login']);
    Route::get('/'.$prefix.'/forms', [FormBuilderPageController::class, 'forms']);
    Route::get('/'.$prefix.'/forms/fill', [FormBuilderPageController::class, 'formsFill']);
    Route::post('/'.$prefix.'/forms/submit', [FormBuilderPageController::class, 'submitPublicForm']);
    Route::get('/'.$prefix.'/track', [FormBuilderPageController::class, 'track']);
    Route::get('/'.$prefix.'/admin', [FormBuilderPageController::class, 'admin']);
    Route::get('/'.$prefix.'/my-submissions', [FormBuilderPageController::class, 'mySubmissions']);
    Route::post('/'.$prefix.'/login', [FormBuilderPageController::class, 'authenticate']);
    Route::post('/'.$prefix.'/logout', [FormBuilderPageController::class, 'logout']);
    Route::get('/dashboard', [FormBuilderPageController::class, 'landing'])->name('dashboard');

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
