<?php

namespace App\Http\Controllers;

use App\Enums\PaymentAccountType;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des comptes de paiements
 * @permission_module Gestion des prestations
 */
class PaymentAccountController extends Controller
{
    public function get_account_payment_status()
    {
        return response()->json([
            'status' => 'success',
            'data'   => PaymentAccountType::toArray(),
        ]);
    }

    /**
     * @permission PaymentAccountController::index
     * @permission_desc Afficher la liste des comptes de paiements
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = PaymentAccount::with(['creator','updater'])
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            });

        if($search = trim($request->input('search'))){
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('is_used_for_consultant', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $category = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $category->items(),
            'current_page' => $category->currentPage(),
            'last_page'    => $category->lastPage(),
            'total'        => $category->total(),
        ]);
    }

    /**
     * @permission PaymentAccountController::store
     * @permission_desc Créer des comptes de paiements
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:payment_accounts,name',
            'code' => 'required|string|unique:payment_accounts,code',
            'account_type' => 'required|string:unique:payment_accounts,account_type',
            'description' => 'nullable|string',
            'is_used_for_consultant' => 'nullable|boolean',
        ]);

        $paymentAccount = PaymentAccount::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'account_type' => $validated['account_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'is_used_for_consultant' => $validated['is_used_for_consultant'] ?? false,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compte de paiement créé avec succès.',
            'data' => $paymentAccount
        ], 201);
    }


    /**
     * @permission PaymentAccountController::update
     * @permission_desc Modifier des comptes de paiements
     */
    public function update(Request $request, string $id)
    {
        $paymentAccount = PaymentAccount::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:payment_accounts,name,' . $paymentAccount->id,
            'code' => 'required|string|unique:payment_accounts,code,' . $paymentAccount->id,
            'account_type' => 'required|string|unique:payment_accounts,account_type,' . $paymentAccount->id,
            'description' => 'nullable|string',
            'is_used_for_consultant' => 'nullable|boolean',
        ]);

        $paymentAccount->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'account_type' => $validated['account_type'],
            'is_active' => true,
            'description' => $validated['description'] ?? null,
            'is_used_for_consultant' => $validated['is_used_for_consultant'] ?? false,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compte de paiement modifié avec succès.',
            'data' => $paymentAccount
        ]);
    }

    /**
     * @permission PaymentAccountController::updateStatus
     * @permission_desc Activer/Désactiver les comptes de paiements
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $paymentAccount = PaymentAccount::findOrFail($id);

        $paymentAccount->update([
            'is_active' => $request->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $paymentAccount->is_active
                ? 'Compte de paiement activé avec succès.'
                : 'Compte de paiement désactivé avec succès.',
            'data' => $paymentAccount
        ]);
    }
}
