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
        }
        else {
            $uniqueName = 'unique:centres,name';
            $uniqueShortName = 'unique:centres,short_name';
        }

        return [
            'name' => ['required',  $uniqueName],
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
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],
        ];
    }
}
