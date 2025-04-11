<?php

namespace App\Http\Controllers;

use App\Models\Assureur;
use Illuminate\Http\Request;
use App\Models\Quotation;

class QuotationController extends Controller
{
    public function index(){
        $quotations = Quotation::where('is_deleted', false)->paginate(10);
        return response()->json($quotations, 200);
    }

    public function show($id){
        $quotations = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if($quotations){
            return response()->json($quotations, 200);
        }else{
            return response()->json(['message' => 'Quotation introuvable'], 404);
        }
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'code'=>'required|unique:quotations',
            'taux' => 'required',
            'description' => 'required',
        ]);
        $quotation = Quotation::create($validated);
        // Return the success response with the created quotation data
        return response()->json([
            'message' => 'Quotation créé avec succès',
            'data' => $quotation
        ]);
    }
    public function getAllCodes()
    {
        $quotations = Quotation::where('is_deleted', false)
            ->select('id', 'code')
            ->get();

        return response()->json([
            'quotations' => $quotations
        ]);
    }



    public function update(Request $request, $id){
        $validated  = $request->validate([
            'code'=>'required|unique:quotations,code,'.$id,
            'taux' => 'required',
            'description' => 'required',
        ]);
        $quotation = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if(!$quotation){
            return response()->json(['message' => 'Quotation not found'], 404);
        }
        $quotation->update($validated);
        return response()->json(['message' => 'Quotation mis à jour avec succès', 'data' => $quotation]);

    }

    public function destroy($id)
    {
        $quotation = Quotation::where('id',$id)->where('is_deleted', false)->first();
        if(!$quotation){
            return response()->json(['message' => 'Devis introuvable'], 404);
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
