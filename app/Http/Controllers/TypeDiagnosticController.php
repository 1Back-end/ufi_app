<?php

namespace App\Http\Controllers;


use App\Models\ConfigTblTypeDiagnostic;
use Illuminate\Http\Request;

class TypeDiagnosticController extends Controller
{
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'code' => 'required|string|unique:config_tbl_type_diagnostic,code',
            'description' => 'required|string',
        ]);

        $validated['created_by'] = $auth->id;

        $type = ConfigTblTypeDiagnostic::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Type diagnostic creé avec success!',
            'type' => $type
        ]);
    }

    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $type = ConfigTblTypeDiagnostic::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:config_tbl_type_diagnostic,code,' . $id,
            'description' => 'required|string',
        ]);

        $validated['updated_by'] = $auth->id;

        $type->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Type diagnostic mis à jour avec succès !',
            'type' => $type
        ]);
    }

    public function show($id)
    {
        $type = ConfigTblTypeDiagnostic::where('is_deleted', false)->findOrFail($id);

        return response()->json([
            'success' => true,
            'type' => $type
        ]);
    }

    public function destroy($id)
    {
        $type = ConfigTblTypeDiagnostic::where('is_deleted', false)->findOrFail($id);
        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type diagnostic supprimé avec succès.'
        ]);
    }
}
