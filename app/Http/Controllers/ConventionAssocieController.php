<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConventionAssocieRequest;
use App\Models\ConventionAssocie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * @permission_category Gestion des conventions
 */
class ConventionAssocieController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission ConventionAssocieController::index
     * @permission_desc Afficher la liste des conventions
     */
    public function index()
    {
        return response()->json([
            'conventions' => ConventionAssocie::with([
                'client',
                'createdBy',
                'updatedBy'
            ])
            ->latest()
            ->when(request('convention'), function (Builder $query) {
                $query->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->whereColumn('amount', '<', 'amount_max');
            })
            ->when(request('search'), function (Builder $query) {
                $query->whereHas('client', function (Builder $query) {
                    $search = request('search');
                    $query->whereLike('nom_cli', "%{$search}%")
                        ->orWhereLike('nomcomplet_client', "%{$search}%")
                        ->orWhereLike('prenom_cli', "%{$search}%")
                        ->orWhereLike('secondprenom_cli', "%{$search}%")
                        ->orWhereLike('ref_cli', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('tel_cli', "%{$search}%")
                        ->orWhereLike('tel2_cli', "%{$search}%");
                });
            })
            ->paginate(
                perPage: request('per_page', 15),
                page: request('page', 1),
            )
        ]);
    }

    /**
     * @param ConventionAssocieRequest $request
     * @return JsonResponse
     *
     * @permission ConventionAssocieController::store
     * @permission_desc Créer une nouvelle convention
     */
    public function store(ConventionAssocieRequest $request)
    {
        try {
            $request->checkValidConventionInProgress();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        ConventionAssocie::create($request->validated());

        return response()->json([
            'message' => __('Convention crée avec succès !')
        ], 201);
    }

    /**
     * @param ConventionAssocieRequest $request
     * @param ConventionAssocie $conventionAssocie
     * @return JsonResponse
     *
     * @permission ConventionAssocieController::update
     * @permission_desc Mettre à jour une convention
     */
    public function update(ConventionAssocieRequest $request, ConventionAssocie $conventionAssocie)
    {
        try {
            $request->checkValidConventionInProgress();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        $conventionAssocie->update($request->validated());

        return response()->json([
            'message' => __("Mise à jour éffectué avec succès !")
        ]);
    }

    /**
     * @param ConventionAssocie $conventionAssocie
     * @return JsonResponse
     *
     * @permission ConventionAssocieController::activate
     * @permission_desc Activer ou désactiver une convention
     */
    public function activate(ConventionAssocie $conventionAssocie)
    {
        if (!$conventionAssocie->active) {
            $convention = ConventionAssocie::where('client_id', $conventionAssocie->client_id)
                ->where('id', '!=', $conventionAssocie->id)
                ->where('active', true)
                ->where('end_date', '>', $conventionAssocie->start_date)
                ->first();

            if ($convention) {
                return response()->json([
                    'message' => 'Une convention en cours est deja enregistrée pour ce client associé !'
                ], 400);
            }
        }

        $conventionAssocie->update([
            'active' => !$conventionAssocie->active
        ]);

        return response()->json();
    }
}
