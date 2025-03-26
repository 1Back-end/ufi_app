<?php

namespace App\Exports;

use App\Models\Consultant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class ConsultantsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Consultant::select(
            'id',
            'ref_consult',
            'nomcomplet_consult',
            'email_consul',
            'tel_consult',
            'tel1_consult',
            'type_consult',
            'status_consult',
            'TelWhatsApp',
            'created_at',
            'updated_at'
        )
            ->where('is_deleted', false) // Ensure only non-deleted consultants are returned
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Référence',
            'Nom & Prénom',
            'Email',
            'N° Téléphone',
            'N° Téléphone Secondaire',
            'Type Consultant',
            'Statut Consultant',
            'Disponible sur WhatsApp',
            'Date de Création',
            'Date de Modification'
        ];
    }
}
