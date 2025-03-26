<?php

use App\Http\Controllers\Authorization\PermissionController;
use App\Http\Controllers\Authorization\RoleController;

Route::middleware(['auth:sanctum', 'check.permission'])->group(function () {
    Route::controller(RoleController::class)->prefix('roles')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{role}', 'show');
        Route::put('/{role}/activate/{activate}', 'activate');
        Route::post('/{role}/permissions', 'assignRoleToPermission');
        Route::post('/{role}/permissions/{permission}/activate/{activate}', 'activateRoleToPermission');
        Route::post('/{role}/users', 'assignRoleToUser');
        Route::post('/{role}/users/{user}/activate/{activate}', 'activateRoleToUser');
    });

    Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{permission}', 'show');
        Route::put('/{permission}/activate/{activate}', 'activate');

        Route::post('/{permission}/roles', 'assignPermissionToRole');
        Route::post('/{permission}/roles/{role}/activate/{activate}', 'activatePermissionToRole');
        Route::post('/{permission}/users', 'assignPermissionToUser');
        Route::post('/{permission}/users/{user}/activate/{activate}', 'activatePermissionToUser');
    });
});
