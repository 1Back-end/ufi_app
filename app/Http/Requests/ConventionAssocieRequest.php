<?php

namespace App\Http\Requests;

use App\Models\ConventionAssocie;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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

    /**
     * @throws Exception
     */
    public function checkValidConventionInProgress(): void
    {
        $convention = $this->route('convention_associe');

        $convention = ConventionAssocie::where('client_id', $this->client_id)
            ->when($convention, function ($query) use ($convention) {
                return $query->where('id', '!=', $convention->id);
            })
            ->where('active', true)
            ->where('end_date', '>', $this->start_date)
            ->first();

        if ($convention) {
            throw new Exception('Une convention en cours est deja enregistrée pour ce client associé ', 400);
        }
    }
}
