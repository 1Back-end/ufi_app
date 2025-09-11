<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @permission_category Gestion des paramètres systèmes
 */
class SettingController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission SettingController::index
     * @permission_desc Afficher la liste des parametres
     */
    public function index(Request $request)
    {
        return response()->json([
            'settings' => Setting::paginate(
                perPage: $request->integer('per_page', 25),
                page: $request->integer('page', 1)
            )
        ]);
    }

    /**
     * @param SettingRequest $request
     * @return JsonResponse
     *
     * @permission SettingController::store
     * @permission_desc Ajouter un parametre
     */
    public function store(SettingRequest $request)
    {
        Setting::create($request->validated());
        return response()->json([
            'message' => __('Operation effectue avec success !'),
        ], 201);
    }

    /**
     * @param SettingRequest $request
     * @param Setting $setting
     * @return JsonResponse
     *
     * @permission SettingController::update
     * @permission_desc Modifier un parametre
     */
    public function update(SettingRequest $request, Setting $setting)
    {
        $setting->update($request->validated());

        return response()->json([
            'message' => __('Operation effectue avec success !'),
        ]);
    }

    /**
     * @param Setting $setting
     * @return JsonResponse
     *
     * @permission SettingController::destroy
     * @permission_desc Supprimer un parametre
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();

        return response()->json();
    }
}
