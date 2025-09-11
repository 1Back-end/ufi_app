<?php

namespace App\Http\Controllers;


use App\Http\Requests\TypeDocumentRequest;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @permission_category Gestion des types de document
 */
class TypeDocumentController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission TypeDocumentController::index
     * @permission_desc Liste des types de document
     */
    public function index()
    {
        return response()->json([
            'type_documents' => TypeDocument::with(['createByTypedoc:id,nom_utilisateur', 'updateByTypedoc:id,nom_utilisateur'])->get()
        ]);
    }

    /**
     * @param TypeDocumentRequest $request
     * @return JsonResponse
     *
     * @permission TypeDocumentController::store
     * @permission_desc Créer un type de document
     */
    public function store(TypeDocumentRequest $request)
    {

        TypeDocument::create([
            'description_typedoc' => $request->description_typedoc,
        ]);

        return response()->json([
            'message' => 'Type document created successfully'
        ], Response::HTTP_CREATED);
    }

    /**
     * @param TypeDocumentRequest $request
     * @param TypeDocument $type_document
     * @return JsonResponse
     *
     * @permission TypeDocumentController::update
     * @permission_desc Mise à jour d’un type de document
     */
    public function update(TypeDocumentRequest $request, TypeDocument $type_document)
    {
        $type_document->update($request->all());

        return response()->json([
            'message' => 'Type document updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @param TypeDocument $type_document
     * @return JsonResponse
     *
     * @permission TypeDocumentController::destroy
     * @permission_desc Supprimer un type de document
     */
    public function destroy(TypeDocument $type_document)
    {
        if ($type_document->clients()->count() > 0) {
            return response()->json([
                'message' => 'Type document ne peut être supprimé car il est utilisé par un client'
            ], Response::HTTP_CONFLICT);
        }


        $type_document->delete();

        return response()->json([
            'message' => 'Type document deleted successfully'
        ], Response::HTTP_ACCEPTED);
    }
}
