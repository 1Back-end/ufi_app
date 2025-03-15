<?php

use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultantController;
use App\Http\Controllers\HopitalController;
use App\Http\Controllers\ServiceHopitalController;
use App\Http\Controllers\TitreController;
use App\Http\Controllers\SpecialiteController;
use App\Http\Controllers\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(ClientController::class)->prefix('clients')->group(function () {
    // Init data for form client
    Route::get('/init-data', 'initData');

    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{client}', 'show');
    Route::put('/{client}', 'update');
    Route::delete('/{client}', 'destroy');
    Route::patch('/{client}/status', 'updateStatus');
    Route::get('/export/clients', 'export');
    Route::get('/print-fidelity-card', 'printFidelityCard');
});

Route::controller(ConsultantController::class)->prefix('consultants')->group(function () {
    Route::get('/list', 'index');  // Afficher la liste des consultants
    Route::post('/create', 'store');  // Ajouter un nouveau consultant
    Route::put('/edit/{id}', 'update');  // Mettre à jour un consultant spécifique
    Route::delete('/delete/{id}', 'destroy');  // Supprimer un consultant spécifique
    Route::put('update_status/{id}/status/{status}', 'updateStatus');
    Route::get('/search', 'search');
    Route::get('/export/consultants', 'export');
    // routes/api.php
});

Route::controller(HopitalController::class)->prefix('hopitals')->group(function () {
    Route::get('/list', 'index');
});
Route::controller(ServiceHopitalController::class)->prefix('services_hopitals')->group(function () {
    Route::get('/list', 'index');
});
Route::controller(TitreController::class)->prefix('titres')->group(function () {
    Route::get('/list', 'index');
});
Route::controller(SpecialiteController::class)->prefix('specialites')->group(function () {
    Route::get('/list', 'index');
});
Route::controller(UserController::class)->prefix('users')->group(function () {
    Route::get('/list', 'index');
});
