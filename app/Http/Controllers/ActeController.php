<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActeRequest;
use App\Models\Acte;

class ActeController extends Controller
{
    public function index()
    {
        return Acte::all();
    }

    public function store(ActeRequest $request)
    {
        return Acte::create($request->validated());
    }

    public function show(Acte $acte)
    {
        return $acte;
    }

    public function update(ActeRequest $request, Acte $acte)
    {
        $acte->update($request->validated());

        return $acte;
    }

    public function destroy(Acte $acte)
    {
        $acte->delete();

        return response()->json();
    }
}
