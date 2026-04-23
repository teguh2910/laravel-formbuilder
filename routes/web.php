<?php

use Illuminate\Support\Facades\Route;
use SatuForm\FormBuilder\Http\Controllers\FormBuilderPageController;

Route::middleware('web')->group(function () {
    $prefix = trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/');
    $basePath = trim((string) config('formbuilder.base_path', ''), '/');
    Route::get('/', [FormBuilderPageController::class, 'landing']);
    if ($basePath !== '') {
        Route::get('/'.$basePath, [FormBuilderPageController::class, 'landing']);
    }
    Route::get('/'.$prefix, [FormBuilderPageController::class, 'landing']);
    Route::get('/'.$prefix.'/login', [FormBuilderPageController::class, 'login']);
    Route::get('/'.$prefix.'/forms', [FormBuilderPageController::class, 'forms']);
    Route::get('/'.$prefix.'/forms/fill', [FormBuilderPageController::class, 'formsFill']);
    Route::post('/'.$prefix.'/forms/submit', [FormBuilderPageController::class, 'submitPublicForm']);
    Route::post('/'.$prefix.'/forms/submit-auth', [FormBuilderPageController::class, 'submitAuthenticatedForm']);
    Route::get('/'.$prefix.'/track', [FormBuilderPageController::class, 'track']);
    Route::get('/'.$prefix.'/admin', [FormBuilderPageController::class, 'admin']);
    Route::get('/'.$prefix.'/my-submissions', [FormBuilderPageController::class, 'mySubmissions']);
    Route::post('/'.$prefix.'/login', [FormBuilderPageController::class, 'authenticate']);
    Route::post('/'.$prefix.'/logout', [FormBuilderPageController::class, 'logout']);
    Route::post('/'.$prefix.'/admin/templates/save', [FormBuilderPageController::class, 'saveTemplate']);
    Route::post('/'.$prefix.'/admin/templates/{id}/toggle-publish', [FormBuilderPageController::class, 'toggleTemplatePublish']);
    Route::post('/'.$prefix.'/admin/templates/{id}/delete', [FormBuilderPageController::class, 'deleteTemplate']);
    Route::post('/'.$prefix.'/admin/users/save', [FormBuilderPageController::class, 'saveUser']);
    Route::post('/'.$prefix.'/admin/users/{id}/delete', [FormBuilderPageController::class, 'deleteUser']);
    Route::post('/'.$prefix.'/admin/submissions/{id}/review', [FormBuilderPageController::class, 'reviewSubmission']);
    $dashboardPath = $basePath !== '' ? '/'.$basePath.'/dashboard' : '/dashboard';
    Route::get($dashboardPath, [FormBuilderPageController::class, 'landing'])->name('dashboard');
});
