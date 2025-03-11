<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultantController;
use App\Exports\ConsultantsExport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::controller(ConsultantController::class)->prefix('consultants')->group(function () {
    Route::get('/', 'index');  // Afficher la liste des consultants
    Route::post('/', 'store');  // Ajouter un nouveau consultant
    Route::put('/{id}', 'update');  // Mettre à jour un consultant spécifique
    Route::delete('/{id}', 'destroy');  // Supprimer un consultant spécifique
    Route::put('/{id}/status/{status}',  'updateStatus');
    Route::get('/search',  'search');
    Route::get('/export', 'export');
    // routes/api.php
});
