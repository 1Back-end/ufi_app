<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrestationRequest;
use App\Models\Prestation;

class PrestationController extends Controller
{
    public function index()
    {
        return Prestation::all();
    }

    public function store(PrestationRequest $request)
    {
        return Prestation::create($request->validated());
    }

    public function show(Prestation $prestation)
    {
        return $prestation;
    }

    public function update(PrestationRequest $request, Prestation $prestation)
    {
        $prestation->update($request->validated());

        return $prestation;
    }

    public function destroy(Prestation $prestation)
    {
        $prestation->delete();

        return response()->json();
    }
}
