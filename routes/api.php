<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CentreController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConsultantController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\HopitalController;
use App\Http\Controllers\PrefixController;
use App\Http\Controllers\ServiceHopitalController;
use App\Http\Controllers\SexeController;
use App\Http\Controllers\SocieteController;
use App\Http\Controllers\SpecialiteController;
use App\Http\Controllers\StatusFamilialeController;
use App\Http\Controllers\TitreController;
use App\Http\Controllers\TypeActeController;
use App\Http\Controllers\TypeDocumentController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\AssureurController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\PriseEnChargeController;
use App\Http\Controllers\VoixTransmissionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UniteProduitController;
use App\Http\Controllers\GroupProduitController;

use Illuminate\Support\Facades\Route;

Route::middleware(['activity'])->group(function () {

    require __DIR__.'/auth.php';
    require __DIR__.'/authorization.php';
    require __DIR__.'/admin.php';

    Route::middleware(['auth:sanctum', 'user.change_password', 'check.permission'])->group(function () {


        // Gestion des centres
        Route::controller(CentreController::class)->prefix('centres')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{centre}', 'show');
            Route::post('/{centre}', 'update');
            Route::delete('/{centre}', 'destroy');
        });

        Route::get('/countries', [CountryController::class, 'index']);

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
            Route::post('/search-duplicates', 'searchDuplicates');
            Route::get('/print-fidelity-card/{client}', 'printFidelityCard');
        });

// Settings routes for clients module
        Route::apiResource('sexes', SexeController::class)->except(['show']);
        Route::apiResource('status-familiales', StatusFamilialeController::class)->except(['show']);
        Route::apiResource('type-documents', TypeDocumentController::class)->except(['show']);
        Route::apiResource('societes', SocieteController::class)->except(['show']);
        Route::apiResource('prefixes', PrefixController::class)->except(['show']);
        Route::apiResource('type-actes', TypeActeController::class)->except(['show']);
        Route::patch('/type-actes/{typeActe}/activate', [TypeActeController::class, 'changeStatus']);

        Route::controller(ConsultantController::class)->prefix('consultants')->group(function () {
            Route::get('/list', 'index');  // Afficher la liste des consultants
            Route::post('/create', 'store');  // Ajouter un nouveau consultant
            Route::put('/edit/{id}', 'update');  // Mettre à jour un consultant spécifique
            Route::delete('/delete/{id}', 'destroy');  // Supprimer un consultant spécifique
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
            Route::get('/search', 'search');
            Route::get('/export', 'export');
            Route::get('/searchandexport', 'searchAndExport');
            Route::get('/get_by_id/{id}','show');

            // routes/api.php
        });

        Route::controller(HopitalController::class)->prefix('hopitals')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_all_hopitals', 'get_all');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');

        });
        Route::controller(ServiceHopitalController::class)->prefix('services_hopitals')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::put('/edit/{id}', 'update');
            Route::get('/get_all_services_hopitals', 'get_all');
            Route::get('/get_by_id/{id}', 'show');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(TitreController::class)->prefix('titres')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_all_titres', 'get_all');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(SpecialiteController::class)->prefix('specialites')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_all_specialites', 'get_all');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
    });
    Route::controller(QuotationController::class)->prefix('quotations')->group(function () {
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
        Route::get('/quotations-data', 'getAllCodes');
    });
    Route::controller(AssureurController::class)->prefix('assureurs')->group(function (){
        route::get('/list','index');
        Route::post('/create','store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
        Route::get('/assureurs-principaux',  'getAssureursPrincipaux');
        Route::delete('/delete/{id}',  'delete');
        Route::put('update_status/{id}/status/{status}', 'updateStatus');
        Route::get('/export-assureurs',  'export');
        Route::get('/search',  'search');
        Route::get('/search-and-export', 'searchAndExport');
    });
    Route::controller(FournisseurController::class)->prefix('fournisseurs')->group(function (){
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'delete');
        Route::get('/search','search');
    });
    Route::controller(PriseEnChargeController::class)->prefix('prise_en_charges')->group(function (){
        Route::get('/list','index');
        Route::post('/create','store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
    Route::controller(VoixTransmissionController::class)->prefix('voie_administrations')->group(function (){
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');

    });
    Route::controller(CategoryController::class)->prefix('category_products')->group(function (){
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
    Route::controller(UniteProduitController::class)->prefix('unity_products')->group(function (){
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
    Route::controller(GroupProduitController::class)->prefix('group_products')->group(function (){
        Route::get('/list', 'index');
        Route::post('/create', 'store');
        Route::get('/get_by_id/{id}', 'show');
        Route::put('/edit/{id}', 'update');
        Route::delete('/delete/{id}', 'destroy');
    });
});
