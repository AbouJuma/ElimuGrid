<?php

use App\Http\Controllers\VirtualClassroomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Virtual Classroom Routes
|--------------------------------------------------------------------------
|
| These routes handle the virtual classroom module with Jitsi Meet
| integration for conducting online classes.
|
*/

Route::group(['middleware' => ['auth', 'customAuth', 'feature_check:Virtual Classroom']], function () {
    // Main resource routes
    Route::get('virtual-classroom', [VirtualClassroomController::class, 'index'])
        ->name('virtual-classroom.index');

    Route::get('virtual-classroom/create', [VirtualClassroomController::class, 'create'])
        ->name('virtual-classroom.create');

    Route::post('virtual-classroom', [VirtualClassroomController::class, 'store'])
        ->name('virtual-classroom.store');

    Route::get('virtual-classroom/{id}/edit', [VirtualClassroomController::class, 'edit'])
        ->name('virtual-classroom.edit');

    Route::put('virtual-classroom/{id}', [VirtualClassroomController::class, 'update'])
        ->name('virtual-classroom.update');

    Route::delete('virtual-classroom/{id}', [VirtualClassroomController::class, 'destroy'])
        ->name('virtual-classroom.destroy');

    // Join and leave meeting
    Route::get('virtual-classroom/{id}/join', [VirtualClassroomController::class, 'join'])
        ->name('virtual-classroom.join');

    Route::post('virtual-classroom/{id}/leave', [VirtualClassroomController::class, 'leave'])
        ->name('virtual-classroom.leave');

    // AJAX endpoints for dynamic filtering
    Route::get('virtual-classroom/get-sections-by-class', [VirtualClassroomController::class, 'getSectionsByClass'])
        ->name('virtual-classroom.get-sections');

    Route::get('virtual-classroom/get-subjects-by-class', [VirtualClassroomController::class, 'getSubjectsByClass'])
        ->name('virtual-classroom.get-subjects');

    // Reports
    Route::get('virtual-classroom/reports', [VirtualClassroomController::class, 'reports'])
        ->name('virtual-classroom.reports');

    // Dashboard widgets
    Route::get('virtual-classroom/upcoming-sessions', [VirtualClassroomController::class, 'upcomingSessions'])
        ->name('virtual-classroom.upcoming');

    Route::get('virtual-classroom/live-sessions', [VirtualClassroomController::class, 'liveSessions'])
        ->name('virtual-classroom.live');
});
