<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('events.index');

Route::get('/events/create', [SubmissionController::class, 'create'])->name('events.create');
Route::post('/events', [SubmissionController::class, 'store'])
    ->middleware('throttle:submissions')
    ->name('events.store');

Route::get('/events/confirm/{event}', [SubmissionController::class, 'showConfirm'])
    ->middleware('signed')
    ->name('events.confirm.show');
Route::post('/events/confirm/{event}', [SubmissionController::class, 'confirm'])
    ->middleware('signed')
    ->name('events.confirm');

Route::get('/events/manage', [SubmissionController::class, 'requestEdit'])->name('events.manage.request');
Route::post('/events/manage', [SubmissionController::class, 'sendEditLink'])
    ->middleware('throttle:submissions')
    ->name('events.manage.send');
Route::get('/events/manage/open', [SubmissionController::class, 'manage'])
    ->middleware('signed')
    ->name('events.manage');

Route::get('/events/{event}/edit', [SubmissionController::class, 'edit'])
    ->middleware('signed')
    ->name('events.edit');
Route::put('/events/{event}', [SubmissionController::class, 'update'])
    ->middleware('signed')
    ->name('events.update');
Route::delete('/events/{event}', [SubmissionController::class, 'destroy'])
    ->middleware('signed')
    ->name('events.destroy');
