<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentDuration;
use App\Enums\AppointmentType;
use App\Enums\DayOfWeek;
use App\Enums\RendezVousStatus;
use App\Enums\RendezVousType;
use App\Enums\TypePrestation;
use App\Exports\PrisesEnChargeExport;
use App\Exports\RendezVousExport;
use App\Models\ConsultantDisponibilite;
use App\Models\DossierConsultation;
use App\Models\RendezVous;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des rendez vous
 * @permission_module Gestion des prestations
 */

class RendezVousController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission RendezVousController::index
     * @permission_desc Afficher la liste des rendez-vous
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        $query = RendezVous::with([
            'client',
            'consultant:id,nomcomplet',
            'createdBy:id,email,nom_utilisateur',
            'updatedBy:id,email,nom_utilisateur',
            'prestation.actes.typeActe',
            'parent:id,code,dateheure_rdv',
        ])
            ->whereNull('deleted_at')
            ->when(!$request->has('start_date') && !$request->has('end_date'), function($q) {
                $q->whereBetween('date_expiration', [
                    \Carbon\Carbon::now()->startOfDay(),
                    \Carbon\Carbon::now()->addMonth()->endOfDay()
                ]);
            })

            ->when($request->filled('start_date') && $request->filled('end_date'), function($q) use ($request) {
                $q->whereBetween('date_expiration', [
                    \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                    \Carbon\Carbon::parse($request->end_date)->endOfDay()
                ]);
            })

            ->when($request->filled('etat'), fn($q) => $q->where('etat', $request->etat))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
            ->when($request->filled('prestation_id'), fn($q) => $q->where('prestation_id', $request->prestation_id))
            ->when($request->filled('type_prestation'), function($q) use ($request) {
                $q->whereHas('prestation', function($subQ) use ($request) {
                    $subQ->where('type', $request->type_prestation);
                });
            })
            ->when($request->filled('type_acte_id'), function($q) use ($request) {
                $q->whereHas('prestation.actes', function($subQ) use ($request) {
                    $subQ->where('type_acte_id', $request->type_acte_id);
                });
            })
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nombre_jour_validite', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQ) => $clientQ->where('nomcomplet_client', 'like', "%{$search}%"))
                        ->orWhereHas('consultant', fn($consultantQ) => $consultantQ->where('nomcomplet', 'like', "%{$search}%"));
                });
            });

        $rendezVous = $query->latest('date_expiration')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $rendezVous->items(),
            'current_page' => $rendezVous->currentPage(),
            'last_page'    => $rendezVous->lastPage(),
            'total'        => $rendezVous->total(),
        ]);
    }

    public function dernieresChronologies($clientId)
    {
        $rendezVous = RendezVous::with([
            'consultant:id,nomcomplet',
            'prestation',
            'createdBy:id,email,nom_utilisateur',
            'updatedBy:id,email,nom_utilisateur',
        ])
            ->where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->latest('dateheure_rdv')
            ->take(5)
            ->get();

        return response()->json([
            'data' => $rendezVous
        ]);
    }

    public function dernieresParametres($clientId)
    {
        $parametres = DossierConsultation::with([
            'rendezVous:id,code,dateheure_rdv',
            'creator:id,email,nom_utilisateur',
            'updater:id,email,nom_utilisateur',
        ])
            ->whereHas('rendezVous', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'data' => $parametres
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::rdvconsultantIndex
     * @permission_desc Afficher la liste des rendez-vous des consultants
     */
    public function rdvconsultantIndex(Request $request)
    {
        $perPage = $request->integer('limit', 25);
        $page    = $request->integer('page', 1);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate   = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        } else {
            $startDate = now()->startOfDay();
            $endDate   = now()->addDays(10)->endOfDay();
        }

        $query = RendezVous::with([
            'client',
            'consultant:id,nomcomplet',
            'createdBy:id,email,nom_utilisateur',
            'updatedBy:id,email,nom_utilisateur',
            'prestation.actes.typeActe',
            'parent:id,code,dateheure_rdv',
        ])
            ->where('doctor_approved', true)
            ->whereBetween('dateheure_rdv', [$startDate, $endDate])

            ->when($request->filled('etat'), fn($q) => $q->where('etat', $request->etat))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
            ->when($request->filled('prestation_id'), fn($q) => $q->where('prestation_id', $request->prestation_id))
            ->when($request->filled('type_prestation'), function($q) use ($request) {
                $q->whereHas('prestation', function($subQ) use ($request) {
                    $subQ->where('type', $request->type_prestation);
                });
            })
            ->when($request->filled('type_acte_id'), function($q) use ($request) {
                $q->whereHas('prestation.actes', function($subQ) use ($request) {
                    $subQ->where('type_acte_id', $request->type_acte_id);
                });
            })
            ->when(trim($request->search), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('nombre_jour_validite', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQ) => $clientQ->where('nomcomplet_client', 'like', "%{$search}%"))
                        ->orWhereHas('consultant', fn($consultantQ) => $consultantQ->where('nomcomplet', 'like', "%{$search}%"));
                });
            });

        $rendezVous = $query->orderBy('dateheure_rdv', 'asc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $rendezVous->items(),
            'current_page' => $rendezVous->currentPage(),
            'last_page'    => $rendezVous->lastPage(),
            'total'        => $rendezVous->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::create_new_rdv
     * @permission_desc Créer un rendez-vous
     */
    public function create_new_rdv(Request $request)
    {
        $rules = [
            'client_id'        => 'required|exists:clients,id',
            'consultant_id'    => 'required|exists:consultants,id',
            'appointment_type' => ['required', Rule::enum(AppointmentType::class)],
            'details'          => 'nullable|string',
        ];

        if ($request->input('appointment_type') === AppointmentType::SCHEDULED->value) {
            $rules['appointment_date'] = 'required|date';
            $rules['appointment_time'] = 'nullable';
            $rules['day_of_week']      = ['required', Rule::enum(DayOfWeek::class)];
        } else {
            $rules['appointment_date'] = 'nullable|date';
            $rules['appointment_time'] = 'nullable';
            $rules['day_of_week']      = ['nullable', Rule::enum(DayOfWeek::class)];
        }

        $validatedData = $request->validate($rules);

        $dernierRdvPaye = RendezVous::where('client_id', $validatedData['client_id'])
            ->where('type', RendezVousType::BILLED->value)
            ->whereHas('prestation', function ($query) {
                $query->where('type', TypePrestation::CONSULTATIONS->value);
            })
            ->latest('dateheure_rdv')
            ->first();

        if ($dernierRdvPaye && $dernierRdvPaye->dateheure_rdv) {
            $dateDernierRdv = \Carbon\Carbon::parse($dernierRdvPaye->dateheure_rdv);
            $joursEcoules = round($dateDernierRdv->diffInDays(now()));

            if ($joursEcoules > 14) {
                throw ValidationException::withMessages([
                    'client_id' => ["Le dernier rendez-vous payé date de plus de 14 jours ({$joursEcoules} jours). Le patient doit se rendre à la caisse pour effectuer un règlement avant de prendre un nouveau rendez-vous."],
                ]);
            }
        }

        if (!empty($validatedData['day_of_week'])) {
            $dayValue = $validatedData['day_of_week'];
            $time = $validatedData['appointment_time'] ?? null;

            $disponibiliteQuery = ConsultantDisponibilite::where('consultant_id', $validatedData['consultant_id'])
                ->where('jour', $dayValue)
                ->where(function ($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                });

            if (!empty($time)) {
                $disponibiliteQuery->where('heure_debut', '<=', $time)
                    ->where('heure_fin', '>=', $time);
            }

            $isDisponible = $disponibiliteQuery->exists();

            if (!$isDisponible) {
                $dayLabel = DayOfWeek::safeLabel($dayValue);

                $errorMessage = !empty($time)
                    ? "Le consultant n'est pas disponible le {$dayLabel} à {$time}."
                    : "Le consultant n'est pas disponible le {$dayLabel}.";

                throw ValidationException::withMessages([
                    'day_of_week' => [$errorMessage],
                ]);
            }
        }

        $client = \App\Models\Client::find($validatedData['client_id']);
        $consultant = \App\Models\Consultant::find($validatedData['consultant_id']);

        $clientName = $client->nomcomplet_client ?? '';
        $consultantName = $consultant->nomcomplet ?? '';

        $dernierePrestationId = $dernierRdvPaye ? $dernierRdvPaye->prestation_id : null;

        $details = $validatedData['details'];

        if (empty($details)) {
            $appointmentTypeEnum = AppointmentType::tryFrom($validatedData['appointment_type']);
            $typeLabel = $appointmentTypeEnum ? $appointmentTypeEnum->label() : 'Rendez-vous';

            $details = "Rendez-vous de type '{$typeLabel}' enregistré pour le client {$clientName} avec le consultant {$consultantName}.";
        }

        if (!empty($validatedData['appointment_date'])) {
            $timePart = $validatedData['appointment_time'] ?? '00:00:00';
            $dateHeureRdv = $validatedData['appointment_date'] . ' ' . $timePart;
        } else {
            $dateHeureRdv = now();
        }

        $appointment = RendezVous::create([
            'client_id'            => $validatedData['client_id'],
            'consultant_id'        => $validatedData['consultant_id'],
            'appointment_type'     => $validatedData['appointment_type'],
            'appointment_date'     => $validatedData['appointment_date'] ?? null,
            'appointment_time'     => $validatedData['appointment_time'] ?? null,
            'day_of_week'          => $validatedData['day_of_week'] ?? null,
            'dateheure_rdv'        => $dateHeureRdv,
            'duration'             => AppointmentDuration::DEFAULT->value,
            'nombre_jour_validite' => AppointmentDuration::DEFAULT->value,
            'details'              => $details,
            'etat'                 => $validatedData['appointment_type'],
            'type'                 => RendezVousType::UNBILLED->value,
            'prestation_id'        => $dernierePrestationId,
            'created_by'           => auth()->id(),
            'doctor_approved'      => true
        ]);

        $appointment->load(['client', 'consultant']);

        return response()->json([
            'message'     => 'Rendez-vous créé avec succès !',
            'appointment' => $appointment
        ], 201);
    }


    /**
     * Display a listing of the resource.
     * @permission RendezVousController::update_rendez_vous
     * @permission_desc Mettre à jour un rendez-vous
     */
    public function update_rendez_vous(Request $request, $id)
    {
        $appointment = RendezVous::findOrFail($id);

        if ($appointment->is_closed) {
            return response()->json([
                'message' => 'Ce rendez-vous est déjà clôturé et ne peut plus être modifié.'
            ], 403);
        }

        $rules = [
            'client_id'        => 'required|exists:clients,id',
            'consultant_id'    => 'required|exists:consultants,id',
            'appointment_type' => ['required', Rule::enum(AppointmentType::class)],
            'details'          => 'nullable|string',
        ];

        if ($request->input('appointment_type') === AppointmentType::SCHEDULED->value) {
            $rules['appointment_date'] = 'required|date';
            $rules['appointment_time'] = 'nullable';
            $rules['day_of_week']      = ['required', Rule::enum(DayOfWeek::class)];
        } else {
            $rules['appointment_date'] = 'nullable|date';
            $rules['appointment_time'] = 'nullable';
            $rules['day_of_week']      = ['nullable', Rule::enum(DayOfWeek::class)];
        }

        $validatedData = $request->validate($rules);

        $dernierRdvPaye = RendezVous::where('client_id', $validatedData['client_id'])
            ->where('id', '!=', $id)
            ->where('type', RendezVousType::BILLED->value)
            ->whereHas('prestation', function ($query) {
                $query->where('type', TypePrestation::CONSULTATIONS->value);
            })
            ->latest('dateheure_rdv')
            ->first();

        if ($dernierRdvPaye && $dernierRdvPaye->dateheure_rdv) {
            $dateDernierRdv = \Carbon\Carbon::parse($dernierRdvPaye->dateheure_rdv);
            $joursEcoules = round($dateDernierRdv->diffInDays(now()));

            if ($joursEcoules > 14) {
                throw ValidationException::withMessages([
                    'client_id' => ["Le dernier rendez-vous payé date de plus de 14 jours ({$joursEcoules} jours). Le patient doit se rendre à la caisse pour effectuer un règlement."],
                ]);
            }
        }

        // Vérification de la disponibilité du consultant
        if (!empty($validatedData['day_of_week'])) {
            $dayValue = $validatedData['day_of_week'];
            $time = $validatedData['appointment_time'] ?? null;

            $disponibiliteQuery = ConsultantDisponibilite::where('consultant_id', $validatedData['consultant_id'])
                ->where('jour', $dayValue)
                ->where(function ($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                });

            if (!empty($time)) {
                $disponibiliteQuery->where('heure_debut', '<=', $time)
                    ->where('heure_fin', '>=', $time);
            }

            $isDisponible = $disponibiliteQuery->exists();

            if (!$isDisponible) {
                $dayLabel = DayOfWeek::safeLabel($dayValue);

                $errorMessage = !empty($time)
                    ? "Le consultant n'est pas disponible le {$dayLabel} à {$time}."
                    : "Le consultant n'est pas disponible le {$dayLabel}.";

                throw ValidationException::withMessages([
                    'day_of_week' => [$errorMessage],
                ]);
            }
        }

        $client = \App\Models\Client::find($validatedData['client_id']);
        $consultant = \App\Models\Consultant::find($validatedData['consultant_id']);

        $clientName = $client->nomcomplet_client ?? '';
        $consultantName = $consultant->nomcomplet ?? '';

        $dernierePrestationId = $dernierRdvPaye ? $dernierRdvPaye->prestation_id : $appointment->prestation_id;

        $details = $validatedData['details'];

        if (empty($details)) {
            $appointmentTypeEnum = AppointmentType::tryFrom($validatedData['appointment_type']);
            $typeLabel = $appointmentTypeEnum ? $appointmentTypeEnum->label() : 'Rendez-vous';

            $details = "Mise à jour du rendez-vous de type '{$typeLabel}' pour le client {$clientName} avec le consultant {$consultantName}.";
        }

        if (!empty($validatedData['appointment_date'])) {
            $timePart = $validatedData['appointment_time'] ?? '00:00:00';
            $dateHeureRdv = $validatedData['appointment_date'] . ' ' . $timePart;
        } else {
            $dateHeureRdv = $appointment->dateheure_rdv ?? now();
        }


        $appointment->update([
            'client_id'            => $validatedData['client_id'],
            'consultant_id'        => $validatedData['consultant_id'],
            'appointment_type'     => $validatedData['appointment_type'],
            'appointment_date'     => $validatedData['appointment_date'] ?? null,
            'appointment_time'     => $validatedData['appointment_time'] ?? null,
            'day_of_week'          => $validatedData['day_of_week'] ?? null,
            'dateheure_rdv'        => $dateHeureRdv,
            'details'              => $details,
            'etat'                 => $validatedData['appointment_type'],
            'prestation_id'        => $dernierePrestationId,
            'updated_by'           => auth()->id(),
        ]);

        $appointment->load(['client', 'consultant']);

        return response()->json([
            'message'     => 'Rendez-vous mis à jour avec succès !',
            'appointment' => $appointment
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission RendezVousController::store
     * @permission_desc Reprogrammer un rendez-vous
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $data = $request->validate([
                'dateheure_rdv'  => 'required|date',
                'details'        => 'required|string',
                'rendez_vous_id' => 'required|exists:rendez_vouses,id',
            ]);

            $firstRendezVous = RendezVous::find($data['rendez_vous_id']);

            $newStart = Carbon::parse($data['dateheure_rdv']);
            $newEnd   = (clone $newStart)->addMinutes($firstRendezVous->duration ?? 30);

            $conflict = RendezVous::where('id', '!=', $firstRendezVous->id)
                ->where(function ($query) use ($firstRendezVous) {
                    $query->where('client_id', $firstRendezVous->client_id)
                        ->orWhere('consultant_id', $firstRendezVous->consultant_id);
                })
                ->where(function ($query) use ($newStart, $newEnd) {
                    $query->where(function ($q) use ($newStart, $newEnd) {
                        $q->where('dateheure_rdv', '<', $newEnd)
                            ->whereRaw('DATE_ADD(dateheure_rdv, INTERVAL COALESCE(duration, 30) MINUTE) > ?', [$newStart]);
                    });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce client ou consultant a déjà un rendez-vous durant cette plage horaire.'
                ], 409);
            }

            $rendezVous = $firstRendezVous->replicate();
            $rendezVous->fill([
                'dateheure_rdv' => $data['dateheure_rdv'],
                'details'       => $data['details'],
                'created_by'    => $auth->id,
                'updated_by'    => $auth->id,
                'type'          => 'Non facturé'
            ]);
            $rendezVous->save();

            return response()->json([
                'success' => true,
                'message' => 'Enregistrement effectué avec succès',
                'data'    => $rendezVous
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::updateStatus
     * @permission_desc Mettre à jour  le statut des rendez-vous ('Actif', 'Inactif', 'Clos', 'No show')
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'etat' => ['required', Rule::enum(RendezVousStatus::class)],
        ]);

        $etat = $request->input('etat');

        $rendez_vous = RendezVous::find($id);

        if (!$rendez_vous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        $hasConsultation = DossierConsultation::where('rendez_vous_id', $id)
            ->exists();

        if ($etat === RendezVousStatus::NO_SHOW->value && $hasConsultation) {
            return response()->json([
                'message' => 'Impossible de marquer comme No show : ce rendez-vous a déjà un dossier de consultation.'
            ], 400);
        }

        $rendez_vous->etat = $etat;
        $rendez_vous->save();

        return response()->json([
            'message' => 'État mis à jour avec succès',
            'rendez_vous' => $rendez_vous
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::export_in_excel
     * @permission_desc Exporter des rendez-vous au format Excel
     */
    public function export_in_excel()
    {
        try {
            $fileName = Str::upper('rendez-vous-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx');
            Excel::store(new RendezVousExport(), $fileName, 'exportrendezvous');
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportrendezvous')->url($fileName)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Une erreur est survenue lors de l'exportation des données.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::show
     * @permission_desc Afficher les details des rendez-vous
     */
    public function show(string $id)
    {
        try {
            $rendez_vous = RendezVous::with([
                    'client',
                    'consultant:id,nomcomplet',
                    'createdBy:id,nom_utilisateur',
                    'updatedBy:id,nom_utilisateur',
                    'prestation'
                ])
                ->findOrFail($id);

            return response()->json([
                'rendez_vous' => $rendez_vous
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Prise en charge introuvable'], 404);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::bulkDeliverImagingResults
     * @permission_desc Remettre les résultats d'imagérie(Radio,Echographie)
     */
    public function bulkDeliverImagingResults(Request $request)
    {
        $request->validate([
            'rendez_vous_ids' => ['required', 'array'],
            'rendez_vous_ids.*' => ['integer', 'exists:rendez_vouses,id'],
            'password' => ['required', 'string'],
        ]);

        $auth = auth()->user();

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect. Impossible de valider la remise.'
            ], 422);
        }

        RendezVous::whereIn('id', $request->rendez_vous_ids)->update([
            'imaging_results_delivered' => true,
            'imaging_delivered_by_user_id' => $auth->id,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Les résultats d’imagerie sélectionnés ont été marqués comme remis avec succès.',
            'count' => count($request->rendez_vous_ids)
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::bulkDeliverNursingResults
     * @permission_desc Remettre les résultats du nursing
     */
    public function bulkDeliverNursingResults(Request $request)
    {
        $request->validate([
            'rendez_vous_ids' => ['required', 'array'],
            'rendez_vous_ids.*' => ['integer', 'exists:rendez_vouses,id'],
            'password' => ['required', 'string'],
        ]);

        $auth = auth()->user();

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect. Impossible de valider la remise.'
            ], 422);
        }

        RendezVous::whereIn('id', $request->rendez_vous_ids)->update([
            'nursing_results_delivered' => true,
            'nursing_delivered_by_user_id' => $auth->id,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Les résultats de nursing sélectionnés ont été marqués comme remis avec succès.',
            'count' => count($request->rendez_vous_ids)
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission RendezVousController::close_rdv
     * @permission_desc Clôturer les rendez-vous en accord avec les médécins
     */
    public function close_rdv(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:appointments,id'
        ]);

        RendezVous::whereIn('id', $request->ids)->update([
            'is_closed' => true,
            'etat'      => RendezVousStatus::CLOSED->value,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Les rendez-vous sélectionnés ont été clôturés avec succès.'
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @permission RendezVousController::no_show_rdv
     * @permission_desc Marquer les rendez-vous en accord avec lés médécins comme non honorés
     */
    public function no_show_rdv(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:appointments,id'
        ]);

        RendezVous::whereIn('id', $request->ids)->update([
            'is_no_show' => true,
            'etat'       => RendezVousStatus::NO_SHOW->value,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Les rendez-vous sélectionnés ont été marqués comme non honorés.'
        ]);
    }



}
