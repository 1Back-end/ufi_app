<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelIdea\Helper\App\Models\_IH_Client_QB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ClientsExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    public function __construct(
        public Builder $clientQuery
    )
    {}

    public function query(): Relation|Builder|_IH_Client_QB|\Laravel\Scout\Builder|\Illuminate\Database\Query\Builder
    {
        return $this->clientQuery;
    }

    public function map($row): array
    {
        return [
            $row->ref_cli,
            $row->nomcomplet_client,
            $row->date_naiss_cli->format('d/m/Y'),
            $row->sexe->description_sex,
            $row->statusFamiliale->description_statusfam,
            $row->enfant_cli ? 'OUI' : 'NON',
            $row->typeDocument->description_typedoc,
            $row->tel_cli,
            $row->tel_whatsapp ? 'OUI' : 'NON',
            $row->tel2_cli,
            $row->societe?->nom_soc_cli,
            $row->type_cli,
            $row->renseign_clini_cli,
            $row->assure_pa_cli ? 'OUI' : 'NON',
            $row->nom_assure_principale_cli,
            $row->nom_conjoint_cli,
            $row->email,
            $row->date_naiss_cli_estime ? 'OUI' : 'NON',
            $row->status_cli === 1 ? 'ACTIVÉ' : ($row->status_cli === 0 ? 'DÉSACTIVÉ' : 'ARCHIVÉ'),
            $row->addresse_cli,
            $row->created_at->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return [
            'Référence',
            'Noms complet',
            'Date de naissance',
            'Sexe',
            'Statut familliale',
            'Enfant',
            'Type de document',
            'Téléphone',
            'Le numéro de téléphone est whatsapp ?',
            'Téléphone 2',
            'Société',
            'Type de client',
            'Renseignement Clinique',
            'Assuré ?',
            'Nom de l\'assuré Principale',
            'Nom du conjoint',
            'Email',
            'Date de naissance Estimée',
            'Status du client',
            'Addresse du client',
            'Date de création',
        ];
    }

    public function columnFormats(): array
    {
        return [
            "C" => NumberFormat::FORMAT_DATE_DDMMYYYY,
            "H" => NumberFormat::FORMAT_NUMBER,
            "J" => NumberFormat::FORMAT_NUMBER,
            "U" => NumberFormat::FORMAT_DATE_DATETIME
        ];
    }
}
