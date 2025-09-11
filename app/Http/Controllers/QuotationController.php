<?php

namespace App\Http\Controllers;

use App\Models\Assureur;
use Illuminate\Http\Request;
use App\Models\Quotation;

/**
 * @permission_category Gestion des cotations
 */
class QuotationController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission QuotationController::index
     * @permission_desc Afficher les quotations
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 10);
        $search = $request->input('search');

        // Construction de la requête
        $query = Quotation::where('is_deleted', false);

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%$search%")
                    ->orWhere('taux', 'like', '%' . $search . '%');
            });
        }

        // Appliquer la pagination après les filtres
        $quotations = $query->paginate($perPage);

        return response()->json([
            'data' => $quotations->items(),
            'current_page' => $quotations->currentPage(),
            'last_page' => $quotations->lastPage(),
            'total' => $quotations->total(),
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission QuotationController::show
     * @permission_desc Afficher les quotations
     */
    public function show($id){
        $quotations = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if($quotations){
            return response()->json($quotations, 200);
        }else{
            return response()->json(['message' => 'Quotation introuvable'], 404);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission QuotationController::store
     * @permission_desc Créer les quotations
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'code'=>'required|unique:quotations',
            'taux' => 'required',
            'description' => 'nullable',
        ]);
        $quotation = Quotation::create($validated);
        // Return the success response with the created quotation data
        return response()->json([
            'message' => 'Quotation créé avec succès',
            'data' => $quotation
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission QuotationController::getAllCodes
     * @permission_desc Afficher l'id et le code des quotations
     */
    public function getAllCodes()
    {
        $quotations = Quotation::where('is_deleted', false)
            ->select('id', 'taux')
            ->get();

        return response()->json([
            'quotations' => $quotations
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission QuotationController::getAllCodesAndTaux
     * @permission_desc Afficher l'id et le code et le prix des quotations
     */
    public function getAllCodesAndTaux()
    {
        $quotations = Quotation::where('is_deleted', false)
            ->select('id', 'code')
            ->get();

        return response()->json([
            'quotations' => $quotations
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission QuotationController::update
     * @permission_desc Mettre à jour des quotations
     */
    public function update(Request $request, $id){
        $validated  = $request->validate([
            'code'=>'required|unique:quotations,code,'.$id,
            'taux' => 'required',
            'description' => 'nullable',
        ]);
        $quotation = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if(!$quotation){
            return response()->json(['message' => 'Quotation not found'], 404);
        }
        $quotation->update($validated);
        return response()->json(['message' => 'Quotation mis à jour avec succès', 'data' => $quotation]);

    }
    /**
     * Display a listing of the resource.
     * @permission QuotationController::destroy
     * @permission_desc Supprimer des quotations
     */
    public function destroy($id)
    {
        $quotation = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if(!$quotation){
            return response()->json(['message' => 'Quotation introuvable'], 404);
        }
        $assureur = Assureur::where('code_quotation', $quotation->id)->count();
        if($assureur > 0){
            return response()->json(['message' => 'La Quotation ne peut pas être car il est associé à des assureurs '], 404);
        }
        $quotation->is_deleted = true;
        $quotation->save();

        return response()->json(['message' => 'Quotation supprimé avec succès']);
    }



    //
}
