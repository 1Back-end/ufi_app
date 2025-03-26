<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\MenuRequest;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @permission MenuController::index
     * @permission_desc Liste des Menus
     */
    public function index(Request $request)
    {
        return response()->json([
           'menus' => Menu::with(['permissions:id,name,description'])->paginate(
               perPage: $request->input('perPage', 10),
               page: $request->input('page', 1)
           )
        ]);
    }

    /**
     * @return JsonResponse
     *
     * @permission MenuController::store
     * @permission_desc Créer un menu et l’associé avec des permissions
     */
    public function store(MenuRequest $request)
    {
        $menu = Menu::create($request->except('permission_ids'));

        if ($request->input('permission_ids')) {
            Permission::findMany($request->input('permission_ids'))->each(function ($permission) use ($menu) {
                $permission->update(['menu_id' => $menu->id]);
            });
        }

        return response()->json([
            'message' => __("Menu crée avec success !")
        ], Response::HTTP_CREATED);
    }

    /**
     * @return JsonResponse
     *
     * @permission MenuController::show
     * @permission_desc Afficher un menu et des permissions associées
     */
    public function show(Menu $menu)
    {
        return \response()->json([
            'menu' => $menu->load('permissions:id,name,description')
        ]);
    }

    /**
     * @return JsonResponse
     *
     * @permission MenuController::update
     * @permission_desc Mise à jou d’un menu et l’associé avec des permissions
     */
    public function update(MenuRequest $request, Menu $menu)
    {
        $menu->update($request->except('permission_ids'));

        if ($request->input('permission_ids')) {
            Permission::findMany($request->input('permission_ids'))->each(function ($permission) use ($menu) {
                $permission->update(['menu_id' => $menu->id]);
            });
        }

        return response()->json([
            'message' => __("Mise à jour effectuée avec succès !")
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @return JsonResponse
     *
     * @permission MenuController::activate
     * @permission_desc Activer / Désactiver un menu
     */
    public function activate(Menu $menu, int $activate)
    {
        $menu->update(['active' => $activate]);

        return response()->json([
            'message' => __("Le Menu a été " .  $activate ? 'activé' : 'désactivé' . " avec succès !")
        ], Response::HTTP_ACCEPTED);
    }
}
