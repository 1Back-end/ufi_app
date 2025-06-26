<?php

use App\Http\Controllers\ActeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CentreController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConsultantController;
use App\Http\Controllers\ConventionAssocieController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\HopitalController;
use App\Http\Controllers\PrefixController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\RegulationMethodController;
use App\Http\Controllers\ServiceHopitalController;
use App\Http\Controllers\SettingController;
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
use App\Http\Controllers\PrestationController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\TypeconsultationController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\TypeSoinsController;
use App\Http\Controllers\SoinsController;
use App\Http\Controllers\OpsTblHospitalisationController;
use App\Http\Controllers\AssurableController;
use App\Http\Controllers\RendezVousController;
use App\Http\Controllers\ConfigTblSousCategorieAntecedentController;
use App\Http\Controllers\CategorieAntecedentController;
use App\Http\Controllers\OpsTblAntecedentController;
use App\Http\Controllers\ConfigTblCategoriesExamenPhysiqueController;
use App\Http\Controllers\ConfigTblTypeDiagnosticController;
use App\Http\Controllers\ConfigTblCategoriesEnquetesController;
use App\Http\Controllers\DossierConsultationController;
use App\Http\Controllers\OpsTblMotifConsultationController;
use App\Http\Controllers\ExamenPhysiqueController;
use App\Http\Controllers\OpsTblEnqueteController;
use App\Http\Controllers\OpsTblRapportConsultationController;
use App\Http\Controllers\OpsTblCertificatMedicalController;
use App\Http\Controllers\OrdonnanceController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\ConfigTblTypeVisiteController;
use App\Http\Controllers\ConfigTblCategorieVisiteController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\OpsTblMiseEnObservationHospitalisationController;
use App\Http\Controllers\OpsTblReferreMedicalController;
use App\Http\Controllers\CategorieDiagnosticController;
use App\Http\Controllers\ConfigSousCategorieDiagnosticController;
use App\Http\Controllers\ConfigTblMaladieDiagnosticController;
use Illuminate\Support\Facades\Route;

Route::middleware(['activity'])->group(function () {

    require __DIR__ . '/auth.php';
    require __DIR__ . '/authorization.php';
    require __DIR__ . '/admin.php';

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
        Route::apiResource('convention-associe', ConventionAssocieController::class)->except(['destroy', 'show']);
        Route::patch('/convention-associe/{convention_associe}/activate', [ConventionAssocieController::class, 'activate']);

        // Settings routes for clients module
        Route::apiResource('sexes', SexeController::class)->except(['show']);
        Route::apiResource('status-familiales', StatusFamilialeController::class)->except(['show']);
        Route::apiResource('type-documents', TypeDocumentController::class)->except(['show']);
        Route::apiResource('societes', SocieteController::class)->except(['show']);
        Route::apiResource('prefixes', PrefixController::class)->except(['show']);
        Route::apiResource('type-actes', TypeActeController::class)->except(['show']);
        Route::patch('/type-actes/{typeActe}/activate', [TypeActeController::class, 'changeStatus']);
        // Actes
        Route::apiResource('actes', ActeController::class)->except(['show']);
        Route::patch('/actes/{acte}/activate', [ActeController::class, 'changeStatus']);

        // Prestations
        Route::get('prestations/types', [PrestationController::class, 'typePrestation']);
        Route::apiResource('prestations', PrestationController::class)->except(['destroy']);
        Route::post('prestations/{prestation}/facture', [PrestationController::class, 'saveFacture']);
        Route::patch('prestations/{prestation}/change-state', [PrestationController::class, 'changeState']);
        Route::apiResource('regulation-methods', RegulationMethodController::class)->except(['show', 'destroy']);
        Route::patch('regulation-methods/{regulationMethod}/activate', [RegulationMethodController::class, 'activate']);
        Route::apiResource('regulations', RegulationController::class)->except(['show', 'index', 'destroy']);
        Route::post('/regulations/{regulation}', [RegulationController::class, 'cancel']);
        Route::get("/factures/in-progress", [PrestationController::class, 'getFacturesInProgress']);
        Route::post('/special-regulations', [RegulationController::class, 'specialRegulation']);
        Route::post('/ignore-factures', [RegulationController::class, 'ignoreFacture']);
        Route::post('/print-facture-assurance', [PrestationController::class, 'printFactureAssurance']);

        Route::controller(ConsultantController::class)->prefix('consultants')->group(function () {
            Route::get('/list', 'index');  // Afficher la liste des consultants
            Route::post('/create', 'store');  // Ajouter un nouveau consultant
            Route::put('/edit/{id}', 'update');  // Mettre à jour un consultant spécifique
            Route::delete('/delete/{id}', 'destroy');  // Supprimer un consultant spécifique
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
            Route::get('/search', 'search');
            Route::get('/export', 'export');
            Route::get('/searchandexport', 'searchAndExport');
            Route::get('/get_by_id/{id}', 'show');

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
        Route::controller(QuotationController::class)->prefix('quotations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/quotations-data', 'getAllCodes');
            Route::get('/quotations-data-code', 'getAllCodesAndTaux');
        });
        Route::controller(AssureurController::class)->prefix('assureurs')->group(function () {
            route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/assureurs-principaux',  'getAssureursPrincipaux');
            Route::delete('/delete/{id}',  'delete');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
            Route::get('/export-assureurs',  'export');
            Route::get('/search',  'search');
            Route::get('/search-and-export', 'searchAndExport');
            Route::get('/get_data',  'listIdName');
            Route::get('/{id}/quotation-code',  'getQuotationCode');
            Route::get('/{id}/hospitalisations', 'getHospitalisations');
        });
        Route::controller(FournisseurController::class)->prefix('fournisseurs')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
            Route::get('/search', 'search');
            Route::get('/get_data',  'listIdName');
            Route::get('/export-fournisseurs',  'export');
            Route::get('/search-and-export', 'searchAndExport');
            Route::get('/search',  'search');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
        });
        Route::controller(PriseEnChargeController::class)->prefix('prise_en_charges')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'getAllClients');
            Route::get('/export-prises-en-charges',  'export');
            Route::get('/search', 'search');
            Route::get('/search-and-export', 'searchAndExport');
        });
        Route::controller(VoixTransmissionController::class)->prefix('voie_administrations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
        });
        Route::controller(CategoryController::class)->prefix('category_products')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
        });
        Route::controller(UniteProduitController::class)->prefix('unity_products')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
        });
        Route::controller(TypeconsultationController::class)->prefix('type_consultants')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
        });
        Route::controller(GroupProduitController::class)->prefix('group_products')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
            Route::get('/{group_product_id}/categories', 'getCategories');
        });
        Route::controller(ProduitController::class)->prefix('products')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
            Route::get('/search', 'search');
            Route::get('/export',  'export');
            Route::get('/search-and-export', 'searchAndExport');
        });
        Route::controller(ConsultationController::class)->prefix('consultations')->group(function (){
            route::get('/list','index');
            Route::post('/create','store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
        });
        Route::controller(TypeSoinsController::class)->prefix('type_soins')->group(function () {
            route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/get_data',  'listIdName');
        });
        Route::controller(SoinsController::class)->prefix('soins')->group(function () {
            route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
        });
        Route::controller(OpsTblHospitalisationController::class)->prefix('hospitalisations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            route::get('/data', 'get_data');
            Route::post('/create','store');
            Route::put('/edit/{id}', 'update');
            Route::put('update_status/{id}/status/{status}', 'updateStatus');
            Route::get('/{id}/pu',  'getPuByHospitalisationId');
        });
        Route::controller(AssurableController::class)->prefix('assurables')->group(function () {
            Route::post('/', 'store');
        });
        Route::controller(RendezVousController::class)->prefix('rendez_vous')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::put('/edit/{id}', 'update');
            Route::put('update_etat/{id}/etat/{etat}', 'updateStatus');
            Route::put('/update_type/{id}/type/{type}', 'toggleType');
            Route::get('/search', 'search');
            Route::get('/export',  'export');
            Route::get('/get_by_id/{id}', 'show');
            Route::get('/client/{client_id}',  'HistoriqueRendezVous');

        });

        Route::controller(ConfigTblSousCategorieAntecedentController::class)->prefix('config_sous_categories_antecedents')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(CategorieAntecedentController::class)->prefix('categorie_antecedents')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::put('update_status/{id}/status/{status}', 'UpdateStatus');
        });
        Route::controller(OpsTblAntecedentController::class)->prefix('ops_tbl_antecedents')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/client/{client_id}',  'index');
        });
        Route::controller(ConfigTblCategoriesExamenPhysiqueController::class)->prefix('config_examen_physiques')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(ConfigTblTypeDiagnosticController::class)->prefix('config_diagnostics')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(ConfigTblCategoriesEnquetesController::class)->prefix('config_enquetes')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(DossierConsultationController::class)->prefix('dossiers_consultations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::post('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/export',  'export');
            Route::get('/search-and-export', 'search_and_export');
            Route::get('/client/{client_id}',  'historiqueClient');

        });
        Route::controller(OpsTblMotifConsultationController::class)->prefix('ops_tbl_motif_consultations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/export',  'export');
            Route::get('/search-and-export', 'search_and_export');
        });
        Route::controller(ExamenPhysiqueController::class)->prefix('examen_physiques')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/export',  'export');

        });

        Route::controller(OpsTblEnqueteController::class)->prefix('ops_tbl_enquetes')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/export',  'export');
        });

        Route::controller(OpsTblRapportConsultationController::class)->prefix('ops_tbl_rapport_consultations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });

        Route::controller(OpsTblCertificatMedicalController::class)->prefix('ops_tbl_certificat_medicals')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(OrdonnanceController::class)->prefix('ordonnances')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(DiagnosticController::class)->prefix('diagnostics')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(ConfigTblTypeVisiteController::class)->prefix('config_tbl_type_visites')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::patch('/{id}/status', 'updateStatus');
        });
        Route::controller(ConfigTblCategorieVisiteController::class)->prefix('config_tbl_categorie_visites')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::patch('/{id}/status', 'updateStatus');
        });
        Route::controller(NurseController::class)->prefix('nurses')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::patch('/{id}/status', 'updateStatus');
            Route::get('/export',  'export');

        });
        Route::controller(OpsTblMiseEnObservationHospitalisationController::class)->prefix('ops_tbl_mise_en_observation_hospitalisations')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/rapport_consultation/{rapport_consultation_id}',  'historiqueByRapport');
        });
        Route::controller(OpsTblReferreMedicalController::class)->prefix('ops_tbl_referre_medicals')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(CategorieDiagnosticController::class)->prefix('categorie_diagnostics')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
            Route::get('/sous_categories_diagnostics/by_categorie/{id}', 'getByCategorie');
            Route::get('/maladies/by_sous_categorie/{id}', 'getBySousCategorie');

        });
        Route::controller(ConfigSousCategorieDiagnosticController::class)->prefix('config_sous_categorie_diagnostics')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });
        Route::controller(ConfigTblMaladieDiagnosticController::class)->prefix('config_tbl_maladie_diagnostics')->group(function () {
            Route::get('/list', 'index');
            Route::post('/create', 'store');
            Route::get('/get_by_id/{id}', 'show');
            Route::put('/edit/{id}', 'update');
            Route::delete('/delete/{id}', 'destroy');
        });

        // Setting management
        Route::apiResource('settings', SettingController::class);
    });
});
