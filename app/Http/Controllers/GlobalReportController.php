<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Mail\PrestationsReportMail;
use App\Models\BilanActeRendezVous;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\ReportRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class GlobalReportController extends Controller
{
    public function sendPrestationsReport(Request $request)
    {
        try {
            $today = Carbon::today();
            $centreId = $request->header('centre');

            // Récupération dynamique du nom du centre
            $centreName = 'GT LABO';
            if ($centreId) {
                $centre = Centre::find($centreId);
                if ($centre && !empty($centre->name)) {
                    $centreName = $centre->name; // ou $centre->nom selon votre colonne
                }
            }

            // Nouveaux clients créés aujourd'hui (filtrés par centre si présent)
            $clientQuery = Client::whereDate('created_at', $today);
            $countClientsCreated = $clientQuery->count();

            // Requête globale des prestations du jour pour ce centre
            $prestationQuery = Prestation::whereDate('created_at', $today);
            if ($centreId) {
                $prestationQuery->where('centre_id', $centreId);
            }

            $totalPrestations = (clone $prestationQuery)->count();

            // Subdivisions des prestations
            $assuranceQuery = (clone $prestationQuery)->whereNotNull('prise_charge_id');
            $countAssurance = (clone $assuranceQuery)->count();
            $assuranceIds   = (clone $assuranceQuery)->pluck('id');

            $clientAssocieQuery = (clone $prestationQuery)->whereNotNull('payable_by');
            $countClientAssocie = (clone $clientAssocieQuery)->count();
            $clientAssocieIds   = (clone $clientAssocieQuery)->pluck('id');

            $comptantQuery = (clone $prestationQuery)->whereNull('prise_charge_id')->whereNull('payable_by');
            $countComptant = (clone $comptantQuery)->count();
            $comptantIds   = (clone $comptantQuery)->pluck('id');

            // Montants facturés (divisés par 100)
            $amountComptant = (Facture::whereIn('prestation_id', $comptantIds)
                    ->when($centreId, fn($q) => $q->where('centre_id', $centreId))
                    ->sum('amount') ?? 0) / 100;

            $amountAssurance = (Facture::whereIn('prestation_id', $assuranceIds)
                    ->when($centreId, fn($q) => $q->where('centre_id', $centreId))
                    ->sum('amount') ?? 0) / 100;

            $amountClientAssocie = (Facture::whereIn('prestation_id', $clientAssocieIds)
                    ->when($centreId, fn($q) => $q->where('centre_id', $centreId))
                    ->sum('amount') ?? 0) / 100;

            $allIds = (clone $prestationQuery)->pluck('id');
            $montantTotal = (Facture::whereIn('prestation_id', $allIds)
                    ->when($centreId, fn($q) => $q->where('centre_id', $centreId))
                    ->sum('amount') ?? 0) / 100;

            // Honoraires Consultants (> 0 et divisés par 100)
            $paidConsultantQuery = (clone $prestationQuery)
                ->where('consultant_amount_status', 'paid')
                ->where('consultant_amount', '>', 0);

            $countConsultantPaid  = $paidConsultantQuery->count();
            $amountConsultantPaid = ($paidConsultantQuery->sum('consultant_amount') ?? 0);

            $pendingConsultantQuery = (clone $prestationQuery)
                ->where('consultant_amount_status', 'available')
                ->where('consultant_amount', '>', 0);

            $countConsultantPending  = $pendingConsultantQuery->count();
            $amountConsultantPending = ($pendingConsultantQuery->sum('consultant_amount') ?? 0);

            $countConsultantTotal  = $countConsultantPaid + $countConsultantPending;
            $amountConsultantTotal = $amountConsultantPaid + $amountConsultantPending;

            $imagerieQuery = Prestation::where('type', TypePrestation::ACTES)
                ->whereDate('created_at', $today)
                ->when($centreId, function ($query, $centreId) {
                    return $query->where('centre_id', $centreId);
                });

            $totalPrestationsImagerie = $imagerieQuery->count();
            $imageriePrestationIds    = $imagerieQuery->pluck('id');

            $bilansQuery = BilanActeRendezVous::whereDate('created_at', $today);

            $totalBilans = $bilansQuery->count();

            $reportData = [
                'centre_name'               => $centreName,
                'date'                      => $today->format('d/m/Y'),
                'total_prestations'         => $totalPrestations,
                'count_clients_created'    => $countClientsCreated,
                'count_comptant'            => $countComptant,
                'amount_comptant'           => $amountComptant,
                'count_assurance'           => $countAssurance,
                'amount_assurance'          => $amountAssurance,
                'count_client_associe'      => $countClientAssocie,
                'amount_client_associe'     => $amountClientAssocie,
                'montant_total'             => $montantTotal,

                'count_consultant_total'    => $countConsultantTotal,
                'amount_consultant_total'   => $amountConsultantTotal,
                'count_consultant_paid'     => $countConsultantPaid,
                'amount_consultant_paid'    => $amountConsultantPaid,
                'count_consultant_pending'  => $countConsultantPending,
                'amount_consultant_pending' => $amountConsultantPending,

                'total_prestations_imagerie'=> $totalPrestationsImagerie,
                'total_bilans'              => $totalBilans,
            ];

            $destinataires = ReportRecipient::pluck('email')->toArray();

            if (empty($destinataires)) {
                $destinataires = [config('mail.from.address')];
            }

            Mail::to($destinataires)->send(new PrestationsReportMail($reportData));

            $destinatairesString = implode(', ', $destinataires);

            return response()->json([
                'status'  => 'success',
                'message' => "Rapport d'activités pour {$centreName} envoyé avec succès à : {$destinatairesString}.",
                'data'    => $reportData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => "Une erreur est survenue lors de l'envoi du rapport d'activités.",
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
