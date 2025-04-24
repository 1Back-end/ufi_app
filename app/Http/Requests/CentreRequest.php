<?php

namespace App\Http\Requests;

use App\Rules\UniqueCentreRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class CentreRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->route('centre')) {
            $uniqueName = (new Unique('centres', 'name'))->ignore($this->route('centre'));
            $uniqueShortName = (new Unique('centres', 'short_name'))->ignore($this->route('centre'));
        } else {
            $uniqueName = 'unique:centres,name';
            $uniqueShortName = 'unique:centres,short_name';
        }

        return [
            'reference' => ['required', 'string', 'max:5', Rule::unique('centres', 'reference')->ignore($this->route('centre')), 'alpha_dash'],
            'name' => ['required', $uniqueName],
            'short_name' => ['required', $uniqueShortName],
            'address' => ['required'],
            'tel' => ['required'],
            'tel2' => ['nullable'],
            'contribuable' => ['required'],
            'registre_commerce' => ['required'],
            'autorisation' => ['required'],
            'town' => ['nullable'],
            'fax' => ['nullable'],
            'email' => ['nullable', 'email', 'max:254'],
            'website' => ['nullable'],
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,svg'],
            'horaires' => ['array', 'required'],
            'horaires.*.day' => ['required', 'integer', 'in:1,2,3,4,5,6,7'],
            'horaires.*.label' => ['required', 'string'],
            'horaires.*.open' => ['nullable', 'date_format:H:i'],
            'horaires.*.close' => ['nullable', 'date_format:H:i'],
            'horaires.*.closed' => ['in:0,1'],
            'postal_code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tel.required' => __("Le numéro de téléphone est requis !"),
            'logo.max' => __("La taille maximale d'un logo doit être de 2Mo"),
            'logo.mimes' => __("Le logo doit prendre en compte uniquement ce type de fichier: jpg, jpeg et png"),
            'reference.required' => __("La référence est requise !"),
            'reference.unique' => __("La référence existe déjà !"),
            'reference.max' => __("La référence doit contenir au maximum 5 caractères !"),
        ];
    }
}
