<?php

namespace App\Http\Controllers;

use App\Enums\RendezVousStatus;
use App\Exports\ConsultantsExport;
use App\Exports\DossierConsultationExport;
use App\Exports\DossierConsultationExportSearch;
use App\Models\DossierConsultation;
use App\Models\PatientArchive;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @permission_category Gestion des dossiers de consultations
 * @permission_module Gestion des prestations
 */

class DossierConsultationController extends Controller
{


    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::index
     * @permission_desc Afficher la liste des dossiers de consultations
     */

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = DossierConsultation::with([
            'emplacement',
            'creator:id,nom_utilisateur',
            'updater:id,nom_utilisateur',
            'rendezVous',
            'rendezVous.client',
            'rendezVous.consultant:id,nomcomplet,ref',
            'medias',
            'rendezVous.prestation',
        ])
            ->when($request->filled('client_id'), function ($q) use ($request) {
                $q->whereHas('rendezVous', fn($subQ) =>
                $subQ->where('client_id', $request->input('client_id'))
                );
            })
            ->when($request->filled('consultant_id'), function ($q) use ($request) {
                $q->whereHas('rendezVous', fn($subQ) =>
                $subQ->where('consultant_id', $request->input('consultant_id'))
                );
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');

                $q->where(function ($subQ) use ($search) {
                    $subQ->where('poids', 'like', "%$search%")
                        ->orWhere('tension_arterielle_bd', 'like', "%$search%")
                        ->orWhere('tension_arterielle_bg', 'like', "%$search%")
                        ->orWhere('code', 'like', "%$search%")
                        ->orWhere('taille', 'like', "%$search%")
                        ->orWhere('temperature', 'like', "%$search%")
                        ->orWhere('frequence_cardiaque', 'like', "%$search%")
                        ->orWhere('saturation', 'like', "%$search%")
                        ->orWhere('autres_parametres', 'like', "%$search%")
                        ->orWhereHas('rendezVous', function ($rsvQ) use ($search) {
                            $rsvQ->where('code', 'like', "%$search%")
                                ->orWhereHas('client', function ($clientQ) use ($search) {
                                    $clientQ->where('nomcomplet_client', 'like', "%$search%")
                                        ->orWhere('ref_cli', 'like', "%$search%");
                                })
                                ->orWhereHas('consultant', function ($consultantQ) use ($search) {
                                    $consultantQ->where('nomcomplet', 'like', "%$search%")
                                        ->orWhere('ref', 'like', "%$search%");
                                });
                        });
                });
            });

        $dossiers = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $dossiers->items(),
            'current_page' => $dossiers->currentPage(),
            'last_page' => $dossiers->lastPage(),
            'total' => $dossiers->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::store
     * @permission_desc Créer des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $data = $request->validate([
            'rendez_vous_id'           => 'required|exists:rendez_vouses,id',
            'location_id'              => 'required|exists:dossier_locations,id',
            'physical_dossier_number'  => 'nullable|string|unique:dossier_consultations,physical_dossier_number',
            'poids'                    => 'required|string',
            'tension_arterielle_bd'    => 'nullable|string',
            'tension_arterielle_bg'    => 'nullable|string',
            'taille'                   => 'nullable|string',
            'saturation'               => 'required|string',
            'autres_parametres'        => 'nullable|string',
            'temperature'              => 'nullable|string',
            'frequence_cardiaque'      => 'nullable|string',
            'fichier_associe'          => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $existing = DossierConsultation::where('rendez_vous_id', $data['rendez_vous_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Un dossier est déjà ouvert pour ce rendez-vous.',
                    'data'    => $existing->load('medias'),
                ], 409);
            }

            $rdv = RendezVous::with('client')->findOrFail($data['rendez_vous_id']);
            $patientId = $rdv->client_id;

            if (empty($data['physical_dossier_number'])) {
                $data['physical_dossier_number'] = $rdv->client->ref_cli ?? null;
            }

            $dossier = DossierConsultation::create(array_merge($data, [
                'created_by' => $auth->id,
            ]));

            $lastArchive = PatientArchive::where('patient_id', $patientId)
                ->orderByDesc('number_order')
                ->first();

            if (!$lastArchive) {
                PatientArchive::create([
                    'patient_id'     => $patientId,
                    'dossier_id'     => $dossier->id,
                    'number_order'   => 1,
                    'first_visit_at' => now(),
                    'last_visit_at'  => now(),
                    'notes'          => 'Première archive du patient créée avec succès.',
                    'created_by'     => $auth->id,
                    'updated_by'     => $auth->id,
                    'location_id'    => $data['location_id'],
                ]);
            } else {
                $lastArchive->update([
                    'last_visit_at' => now(),
                    'notes'         => 'Archive mise à jour suite à la dernière consultation.',
                    'updated_by'    => $auth->id,
                ]);

                PatientArchive::create([
                    'patient_id'     => $patientId,
                    'dossier_id'     => $dossier->id,
                    'number_order'   => $lastArchive->number_order + 1,
                    'first_visit_at' => $lastArchive->first_visit_at,
                    'last_visit_at'  => now(),
                    'notes'          => 'Archive mise à jour suite à la dernière consultation.',
                    'created_by'     => $auth->id,
                    'updated_by'     => $auth->id,
                    'location_id'    => $data['location_id'],
                ]);
            }

            $rdv->update(['etat' => RendezVousStatus::TAKEN_FOR_CONSULTATION->value]);

            if ($request->hasFile('fichier_associe')) {
                $file = $request->file('fichier_associe');
                $path = $file->store('dossiers', 'public');

                $dossier->medias()->create([
                    'name'      => $file->getClientOriginalName(),
                    'disk'      => 'public',
                    'path'      => $path,
                    'filename'  => $file->hashName(),
                    'mimetype'  => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Dossier et archive créés avec succès.',
                'data'    => $dossier->load('medias'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur création dossier : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la création du dossier.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::update_dossiers
     * @permission_desc Modification des dossiers de consultations
     */
    public function update_dossiers(Request $request, $id)
    {
        $auth = auth()->user();

        $dossier = DossierConsultation::findOrFail($id);

        $data = $request->validate([
            'rendez_vous_id'           => 'nullable|exists:rendez_vouses,id',
            'location_id'              => 'required|exists:dossier_locations,id',
            'physical_dossier_number'  => 'nullable|string|unique:dossier_consultations,physical_dossier_number,' . $id,
            'poids'                    => 'required|string',
            'tension_arterielle_bd'    => 'nullable|string',
            'tension_arterielle_bg'    => 'nullable|string',
            'taille'                   => 'nullable|string',
            'saturation'               => 'required|string',
            'autres_parametres'        => 'nullable|string',
            'temperature'              => 'nullable|string',
            'frequence_cardiaque'      => 'nullable|string',
            'fichier_associe'          => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $existing = DossierConsultation::where('rendez_vous_id', $data['rendez_vous_id'])
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Un autre dossier est déjà ouvert pour ce rendez-vous.',
                    'data'    => $existing->load('medias'),
                ], 409);
            }

            $rdv = RendezVous::with('client')->findOrFail($data['rendez_vous_id']);
            $patientId = $rdv->client_id;

            if (empty($data['physical_dossier_number'])) {
                $data['physical_dossier_number'] = $rdv->client->ref_cli ?? null;
            }

            $dossier->update(array_merge($data, [
                'updated_by' => $auth->id,
            ]));

            $archive = PatientArchive::where('dossier_id', $dossier->id)->first();
            if ($archive) {
                $archive->update([
                    'location_id' => $data['location_id'],
                    'notes'       => 'Archive mise à jour suite à la modification du dossier.',
                    'updated_by'  => $auth->id,
                ]);
            }

            $rdv->update(['etat' => RendezVousStatus::IN_PROGRESS->value]);

            if ($request->hasFile('fichier_associe')) {
                $file = $request->file('fichier_associe');
                $path = $file->store('dossiers', 'public');

                $dossier->medias()->create([
                    'name'      => $file->getClientOriginalName(),
                    'disk'      => 'public',
                    'path'      => $path,
                    'filename'  => $file->hashName(),
                    'mimetype'  => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Dossier mis à jour avec succès.',
                'data'    => $dossier->load('medias'),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour dossier : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour du dossier.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::show
     * @permission_desc Afficher les détails des dossiers de consultations
     */
    public function show(string $id)
    {
        $dossiers = DossierConsultation::with([
                'emplacement',
                'creator:id,nom_utilisateur',
                'updater:id,nom_utilisateur',
                'rendezVous',
                'rendezVous.client',
                'rendezVous.consultant:id,nomcomplet,ref',
                'medias',
                'rendezVous.prestation',
            ])
            ->findOrFail($id);

        if (!$dossiers) {
            return response()->json(['message' => 'Dossiers introuvable'], 404);
        } else {
            return response()->json($dossiers);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission DossierConsultationController::export_in_excel
     * @permission_desc Exporter des dossiers de consultations en excel
     */
    public function export_in_excel()
    {
        $fileName = Str::upper('dossiers-consultations-' . Carbon::now()->format('Y-m-d') . '.xlsx');

        Excel::store(new DossierConsultationExport(), $fileName, 'dossiersconsultations');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('dossiersconsultations')->url($fileName)
        ]);
    }

}
