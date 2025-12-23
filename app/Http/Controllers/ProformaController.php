<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use App\Models\Proforma;
use App\Models\ProformaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion des proformas
 */
class ProformaController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ProformaController::index
     * @permission_desc Afficher la liste des proformas
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);  // Par défaut 25 éléments par page
        $page = $request->input('page', 1);

        // Construction de la requête
        $query = Proforma::with([
            'items',
            'creator',
            'updater',
            'centre',
            'quotation',
            'client'
        ])
            ->where('centre_id', $request->header('centre'))
            ->where('is_deleted', false);

        // Filtres optionnels
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('quotation_id')) {
            $query->where('quotation_id', $request->quotation_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
        }

        // Recherche texte
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('b_global', 'like', "%$search%")
                    ->orWhere('proforma', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhereHas('quotation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%")
                            ->orWhere('taux', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%");
                    })
                    ->orWhereHas('client', function ($q3) use ($search) {
                        $q3->where('nomcomplet_client', 'like', "%$search%")
                            ->orWhere('ref_cli', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%")
                            ->orWhere('tel_cli', 'like', "%$search%")
                            ->orWhere('tel2_cli', 'like', "%$search%")
                            ->orWhere('prenom_cli', 'like', "%$search%")
                            ->orWhere('nom_cli', 'like', "%$search%");
                    });
            });
        }

        // Exécution de la requête avec pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        // Retour de la réponse JSON
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);
    }



    /**
     * Display a listing of the resource.
     * @permission ProformaController::store
     * @permission_desc Création des proformas
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        // Vérification du centre dans l'en-tête
        $centreId = $request->header('centre');
        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validation des données
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'price_kb_prelevement' => 'nullable|numeric|min:0',
            'b_global' => 'nullable|in:b,b1', // ici c'est directement le b ou b1 sélectionné
            'proforma' => 'boolean',
            'total' => 'required|numeric', // total envoyé par le frontend
            'elements' => 'required|array|min:1',
            'elements.*.name' => 'required|string',
            'elements.*.price' => 'required|numeric',
            'elements.*.kb_prelevement' => 'nullable|numeric',
            'elements.*.type' => 'required|integer',
            'type' => 'nullable|integer',

        ]);

        DB::beginTransaction();

        try {
            // Récupération du centre
            $centre = Centre::findOrFail($centreId);

            // Génération du code : référence du centre + année + 3 caractères aléatoires
            $code = $centre->reference . now()->format('Ymd') . Str::upper(Str::random(7));

            // Création de la proforma avec total et b_global venant du frontend
            $proforma = Proforma::create([
                'code' => $code,
                'client_id' => $request->client_id,
                'quotation_id' => $request->quotation_id,
                'b_global' => $request->b_global, // valeur envoyée (b ou b1)
                'proforma' => $request->boolean('proforma', true),
                'total' => $request->total, // total envoyé depuis le frontend
                'centre_id' => $centreId,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'type' => $request->type,
                'status' => 'pending',
                'price_kb_prelevement' => $request->price_kb_prelevement,
            ]);

            // Création des items (unit_price, kb_prelevement, total)
            foreach ($request->elements as $el) {
                ProformaItem::create([
                    'proforma_id' => $proforma->id,
                    'name' => $el['name'],
                    'unit_price' => $el['price'],
                    'kb_prelevement' => $el['kb_prelevement'] ?? 0,
                    'total' => $el['price'] + ($el['kb_prelevement'] ?? 0),
                    'type' => $el['type'],
                    'b_value'       => $el['b_value'] ?? 0, // valeur du b ou b1
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Proforma créée avec succès',
                'proforma' => $proforma->load('items', 'client', 'quotation', 'centre')
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de la proforma',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission ProformaController::update
     * @permission_desc Modification des proformas
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        // Vérification du centre
        $centreId = $request->header('centre');
        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validation
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'b_global' => 'nullable|in:b,b1',
            'proforma' => 'boolean',
            'total' => 'required|numeric',
            'elements' => 'required|array|min:1',
            'elements.*.name' => 'required|string',
            'elements.*.price' => 'required|numeric',
            'elements.*.kb_prelevement' => 'nullable|numeric',
            'elements.*.type' => 'required|integer',
            'price_kb_prelevement' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $proforma = Proforma::where('id', $id)
                ->where('centre_id', $centreId)
                ->firstOrFail();

            // Mise à jour de la proforma
            $proforma->update([
                'client_id' => $request->client_id,
                'quotation_id' => $request->quotation_id,
                'b_global' => $request->b_global,
                'proforma' => $request->boolean('proforma', true),
                'total' => $request->total,
                'updated_by' => $auth->id,
                'price_kb_prelevement' => $request->price_kb_prelevement,
            ]);

            // Supprimer les anciens items
            ProformaItem::where('proforma_id', $proforma->id)->delete();

            // Recréer les nouveaux items
            foreach ($request->elements as $el) {
                ProformaItem::create([
                    'proforma_id' => $proforma->id,
                    'name' => $el['name'],
                    'unit_price' => $el['price'],
                    'kb_prelevement' => $el['kb_prelevement'] ?? 0,
                    'total' => $el['price'] + ($el['kb_prelevement'] ?? 0),
                    'type' => $el['type'],
                    'b_value' => $el['b_value'] ?? 0,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Proforma mise à jour avec succès',
                'proforma' => $proforma->load('items', 'client', 'quotation', 'centre')
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la proforma',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ProformaController::show
     * @permission_desc Afficher les détails des proformas
     */
    public function show($id)
    {
        $proforma = Proforma::with([
            'items.examen',
            'creator',
            'updater',
            'centre',
            'quotation',
            'client'
        ])->findOrFail($id);

        return response()->json([
            'status' => Response::HTTP_OK,
            'proforma' => $proforma,
            'total_unit_price' => $proforma->getTotalUnitPriceAttribute(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProformaController::cancel
     * @permission_desc Annulation des proformas
     */
    public function cancel($id)
    {
        $auth = auth()->user();

        $proforma = Proforma::findOrFail($id);

        // Empêche l'annulation si déjà annulée
        if ($proforma->status === 'canceled') {
            return response()->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => "Cette proforma est déjà annulée."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Empêche l'annulation si validée ou payée (si ces statuts existent)
        if (in_array($proforma->status, ['validated', 'paid'])) {
            return response()->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => "Vous ne pouvez pas annuler une proforma déjà validée ou payée."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour
        $proforma->update([
            'status'     => 'cancel',
            'updated_by' => $auth->id
        ]);

        return response()->json([
            'status'   => Response::HTTP_OK,
            'message'  => "Proforma annulée avec succès.",
            'proforma' => $proforma
        ], Response::HTTP_OK);
    }


    /**
     * Display a listing of the resource.
     * @permission ProformaController::print_proforma
     * @permission_desc Imprimer les factures des proformas
     */
    public function print_proforma(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $proforma = Proforma::with([
                'items',
                'creator',
                'updater',
                'centre',
                'quotation',
                'client'
            ])
                ->where('centre_id', $request->header('centre'))
                ->where('id', $id)
                ->firstOrFail();


            if (!$proforma) {
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            // Récupération du centre et logo
            $centre = Centre::find($request->header('centre'));
            $media  = $centre->medias()->where('name', 'logo')->first();
            $logo   = $media ? 'storage/' . $media->path . '/' . $media->filename : '';

            $data = [
                'proforma' => $proforma,
                'logo'     => $logo,
                'centre'   => $centre,
            ];

            // Chemins du fichier PDF
            $fileName   = strtoupper('PROFORMA-' . now()->format('YmdHis') . '.pdf');
            $folderPath = 'storage/details-proforma/' . $proforma->id;
            $filePath   = $folderPath . '/' . $fileName;

            if (!is_dir($folderPath) && !mkdir($folderPath, 0755, true) && !is_dir($folderPath)) {
                throw new \RuntimeException("Impossible de créer le répertoire : {$folderPath}");
            }

            $footer = 'pdfs.reports.factures.footer';

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.details-proforma.details-proforma',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [10, 10, 10, 10],
                footer: $footer
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => "Le fichier PDF n'a pas été généré."], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64     = base64_encode($pdfContent);

            return response()->json([
                'proforma' => $proforma,
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la génération du PDF.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }





    //
}
