<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConventionAssocieRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'date' => ['required', 'date'],
            'amount_max' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ];
    }
}
