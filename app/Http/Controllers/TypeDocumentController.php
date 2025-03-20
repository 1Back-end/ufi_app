<?php

namespace App\Http\Controllers;


use App\Http\Requests\TypeDocumentRequest;
use App\Models\TypeDocument;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class TypeDocumentController extends Controller
{
    public function index()
    {
        return response()->json([
            'type_documents' => TypeDocument::with(['createByTypedoc:id,nom_utilisateur', 'updateByTypedoc:id,nom_utilisateur'])->get()
        ]);
    }

    public function store(TypeDocumentRequest $request)
    {
        $auth = User::first();
//        $auth = auth()->user();
        TypeDocument::create([
            'description_typedoc' => $request->description_typedoc,
            'create_by_typedoc' => $auth->id,
            'update_by_typedoc' => $auth->id
        ]);

        return response()->json([
            'message' => 'Type document created successfully'
        ], Response::HTTP_CREATED);
    }

    public function update(TypeDocumentRequest $request, TypeDocument $type_document)
    {
        $auth = User::first();
        $data = array_merge($request->all(), ['update_by_typedoc' => $auth->id]);

        $type_document->update($data);

        return response()->json([
            'message' => 'Type document updated successfully'
        ], Response::HTTP_ACCEPTED);
    }

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
