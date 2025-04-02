<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prenom' => ['nullable'],
            'nom_utilisateur' => ['required'],
            'password' => ['required'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'login' => ['required', 'unique:users,login'],
            'centres' => ['array', 'required'],
            'centres.*.id' => ['required', 'exists:centres,id'],
            'centres.*.default' => ['required', 'boolean'],
        ];
    }
}
