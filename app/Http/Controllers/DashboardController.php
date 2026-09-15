<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\Regulation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion du tableau de bord d'activité
 * @permission_module Gestion des prestations
 * @permission_module Gestion des caisses
 * @permission_module Gestion du laboratoire
 * @permission_module Gestion des stocks
 * @permission_module Paramètres Facturations
 * @permission_module Paramètres Applicatifs
 * @permission_module Gestion des rapports
 */
class DashboardController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission DashboardController::get_data_for_nursing
     * @permission_desc Statistiques sur les activités lié au sevvice du nursning
     */
    public function get_data_for_nursing(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::yesterday()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::yesterday()->endOfDay();

        $prestationsQuery = Prestation::where('type', TypePrestation::CONSULTATIONS)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalPrestations = $prestationsQuery->count();
        $prestationIds = $prestationsQuery->pluck('id');


        $dossiersQuery = \App\Models\DossierConsultation::whereBetween('created_at', [$startDate, $endDate]);

        $totalDossiers = $dossiersQuery->count();
        $dossiersList = $dossiersQuery->get(['id', 'created_at', 'created_by']);

        $surplusCount = max(0, $totalDossiers - $totalPrestations);
        $deficitCount = max(0, $totalPrestations - $totalDossiers);

        if ($totalPrestations === 0 && $totalDossiers === 0) {
            $message = "Aucune prestation ni aucun dossier de consultation enregistrés sur cette période.";
        } elseif ($totalDossiers === 0 && $totalPrestations > 0) {
            $message = "Attention : Aucun dossier de consultation n'a été ouvert pour ces {$totalPrestations} prestation(s) enregistrée(s).";
        } elseif ($totalDossiers === $totalPrestations) {
            $message = "Le nombre de dossiers correspond parfaitement aux prestations enregistrées.";
        } elseif ($surplusCount > 0) {
            $message = "Il y a un surplus de {$surplusCount} dossier(s) par rapport aux prestations enregistrées.";
        } else {
            $message = "Attention : Il manque {$deficitCount} dossier(s) par rapport aux prestations enregistrées.";
        }

        $distributionByiciaire = \App\Models\DossierConsultation::whereBetween('created_at', [$startDate, $endDate])
            ->select('created_by', \DB::raw('count(*) as total'))
            ->with('creator:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->created_by,
                    'user_name' => $item->creator->nom_utilisateur ?? 'Inconnu',
                    'total_dossiers' => $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => $message,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'data' => [
                'total_prestations_consultations' => $totalPrestations,
                'prestation_ids' => $prestationIds,
                'total_dossiers_consultation' => $totalDossiers,
                'dossiers_list' => $dossiersList,
                'surplus_dossiers' => $surplusCount,
                'deficit_dossiers' => $deficitCount,
                'distribution_dossiers_by_user' => $distributionByiciaire,
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission DashboardController::get_data_for_imagerie
     * @permission_desc Statistiques sur les activités lié au service d'imagerie
     */
    public function get_data_for_imagerie(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::yesterday()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::yesterday()->endOfDay();

        $prestationsQuery = Prestation::where('type', TypePrestation::ACTES)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalPrestations = $prestationsQuery->count();
        $prestationIds = $prestationsQuery->pluck('id');

        $bilansQuery = \App\Models\BilanActeRendezVous::whereBetween('created_at', [$startDate, $endDate]);

        $totalBilans = $bilansQuery->count();
        $bilansList = $bilansQuery->get(['id', 'created_at', 'created_by', 'titre']);

        $surplusCount = max(0, $totalBilans - $totalPrestations);
        $deficitCount = max(0, $totalPrestations - $totalBilans);

        if ($totalPrestations === 0 && $totalBilans === 0) {
            $message = "Aucune prestation ni aucun bilan d'imagerie enregistrés sur cette période.";
        } elseif ($totalBilans === 0 && $totalPrestations > 0) {
            $message = "Attention : Aucun bilan d'imagerie n'a été établi pour ces {$totalPrestations} prestation(s) enregistrée(s).";
        } elseif ($totalBilans === $totalPrestations) {
            $message = "Le nombre de bilans d'imagerie correspond parfaitement aux prestations enregistrées.";
        } elseif ($surplusCount > 0) {
            $message = "Il y a un surplus de {$surplusCount} bilan(s) d'imagerie par rapport aux prestations enregistrées.";
        } else {
            $message = "Attention : Il manque {$deficitCount} bilan(s) d'imagerie par rapport aux prestations enregistrées.";
        }

        // 3. Distribution par créateur
        $distributionByiciaire = \App\Models\BilanActeRendezVous::whereBetween('created_at', [$startDate, $endDate])
            ->select('created_by', \DB::raw('count(*) as total'))
            ->with('creator:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->created_by,
                    'user_name' => $item->creator->nom_utilisateur ?? 'Inconnu',
                    'total_dossiers' => $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => $message,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'data' => [
                'total_prestations_imagerie' => $totalPrestations,
                'prestation_ids' => $prestationIds,
                'total_bilans_imagerie' => $totalBilans,
                'bilans_list' => $bilansList,
                'surplus_bilans' => $surplusCount,
                'deficit_bilans' => $deficitCount,
                'distribution_dossiers_by_user' => $distributionByiciaire,
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission DashboardController::get_prestations_and_factures
     * @permission_desc Statistiques sur les prestations et les factures
     */
    public function get_prestations_and_factures(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        // Prestations
        $totalPrestations = Prestation::whereBetween('created_at', [$startDate, $endDate])->count();
        $distributionPrestations = Prestation::whereBetween('created_at', [$startDate, $endDate])
            ->select('created_by', DB::raw('count(*) as total'))
            ->with('createdBy:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->created_by,
                'user_name' => $item->createdBy->nom_utilisateur ?? 'Inconnu',
                'total' => $item->total,
            ]);

        // Factures
        $facturesQuery = Facture::whereBetween('created_at', [$startDate, $endDate]);
        $totalFactures = $facturesQuery->count();
        $montantTotalFactures = $facturesQuery->sum('amount');
        $distributionFactures = $facturesQuery->select('created_by', DB::raw('count(*) as total_count'), DB::raw('sum(amount) as total_amount'))
            ->with('createdBy:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->created_by,
                'user_name' => $item->createdBy->nom_utilisateur ?? 'Inconnu',
                'total_count' => $item->total_count,
                'total_amount' => $item->total_amount,
            ]);

        return response()->json([
            'success' => true,
            'filters' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
            'data' => [
                'prestations' => ['total' => $totalPrestations, 'distribution_by_user' => $distributionPrestations],
                'factures' => ['total_count' => $totalFactures, 'total_amount' => $montantTotalFactures, 'distribution_by_user' => $distributionFactures],
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission DashboardController::get_factures_and_encaissements
     * @permission_desc Statistiques sur les factures et les encaissements
     */
    public function get_factures_and_encaissements(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        $facturesQuery = Facture::whereBetween('created_at', [$startDate, $endDate]);
        $totalFactures = $facturesQuery->count();
        $montantTotalFactures = $facturesQuery->sum('amount');
        $distributionFactures = $facturesQuery->select('created_by', DB::raw('count(*) as total_count'), DB::raw('sum(amount) as total_amount'))
            ->with('createdBy:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->created_by,
                'user_name' => $item->createdBy->nom_utilisateur ?? 'Inconnu',
                'total_count' => $item->total_count,
                'total_amount' => $item->total_amount,
            ]);

        $regulationsQuery = Regulation::whereBetween('created_at', [$startDate, $endDate]);
        $totalEncaissementsCount = $regulationsQuery->count();
        $montantTotalEncaissements = $regulationsQuery->sum('amount');
        $distributionEncaissements = $regulationsQuery->select('created_by', DB::raw('count(*) as total_count'), DB::raw('sum(amount) as total_amount'))
            ->with('createdBy:id,nom_utilisateur')
            ->groupBy('created_by')
            ->get()
            ->map(fn($item) => [
                'user_id' => $item->created_by,
                'user_name' => $item->createdBy->nom_utilisateur ?? 'Inconnu',
                'total_count' => $item->total_count,
                'total_amount' => $item->total_amount,
            ]);

        return response()->json([
            'success' => true,
            'filters' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
            'data' => [
                'factures' => ['total_count' => $totalFactures, 'total_amount' => $montantTotalFactures, 'distribution_by_user' => $distributionFactures],
                'encaissements' => ['total_count' => $totalEncaissementsCount, 'total_amount' => $montantTotalEncaissements, 'distribution_by_user' => $distributionEncaissements],
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission DashboardController::getConnectedUsersByDate
     * @permission_desc Statistiques sur les utilisateurs connectés et le suivi de leur présence
     */
    public function getConnectedUsersByDate(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::today()->endOfDay();

        $users = User::where('connected', true)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->get([
                'id',
                'nom_utilisateur',
                'prenom',
                'email',
                'first_connection',
                'last_connected',
                'updated_at'
            ]);

        return response()->json([
            'success' => true,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'total' => $users->count(),
            'users' => $users
        ], Response::HTTP_OK);
    }
}
