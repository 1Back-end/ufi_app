<?php

namespace App\Exports;

use App\Models\PriseEnCharge;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PrisesEnChargeExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $prises_en_charges = PriseEnCharge::where('is_deleted', false)->get();

        if ($prises_en_charges->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $prises_en_charges->map(function ($prise_en_charge) {
            return [
                '#' => $prise_en_charge->id,
                'Assureur' => $prise_en_charge->assureur?->nom ?? 'N/A',
                'Client' => $prise_en_charge->client?->nomcomplet_client ?? 'N/A',
                'Quotation' => $prise_en_charge->quotation->taux ? $prise_en_charge->quotation->taux . '%' : 'N/A',
                'Date' => $prise_en_charge->date?->format('Y-m-d') ?? 'N/A',
                'Date Début' => $prise_en_charge->date_debut?->format('Y-m-d') ?? 'N/A',
                'Date Fin' => $prise_en_charge->date_fin?->format('Y-m-d') ?? 'N/A',
                'Taux (%)' => $prise_en_charge->taux_pc . '%',
                'Usage Unique' => $prise_en_charge->usage_unique ? 'Oui' : 'Non',
                'Créé le' => $prise_en_charge->created_at?->format('d/m/Y H:i:s') ?? 'N/A',
                'Par' => $prise_en_charge->creator?->email ?? 'N/A',
                'Modifié le' => $prise_en_charge->updated_at?->format('d/m/Y H:i:s') ?? 'N/A',
                'Par (modif)' => $prise_en_charge->updater?->email ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Assureur',
            'Client',
            'Quotation',
            'Date',
            'Date Début',
            'Date Fin',
            'Taux (%)',
            'Usage Unique',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
        ];
    }
}
