<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ChangeDefaultPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'login' => ['required', 'exists:users,login'],
            'password' => ['required'],
            'new_password' => ['required']
        ]);

        $user = User::whereLogin($request->get('login'))->first();

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'Le nouveau mot de passe doit différer du mot de passe actuel.'], Response::HTTP_CONFLICT);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Le mot de passe a été changé avec succès !']);
    }
}
