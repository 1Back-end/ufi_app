<?php

use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
