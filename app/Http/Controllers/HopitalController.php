<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hopital;

class HopitalController extends Controller
{
    public function index(){
        $hopital = Hopital::select('id','nom_hopi')->get();
        return response()->json($hopital);
    }
    //
}
