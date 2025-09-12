<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\ConventionAssocie;
use App\Models\Prefix;
use App\Models\Societe;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportAssociateClient implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return Model|Client|null
     */
    public function model(array $row): Model|Client|null
    {
//        dd($row);

        $societe = null;
        if ($row['soc_cli'] && $row['soc_cli'] !== 'NULL') {
            $societe = Societe::firstOrCreate([
                'nom_soc_cli' => $row['soc_cli']
            ], [
                'nom_soc_cli' => $row['soc_cli'],
            ]);
        }

        $prefix = Prefix::firstOrCreate([
            'prefixe' => $row['prefixe']
        ], [
            'prefixe' => $row['prefixe'],
            'position' => 0
        ]);

        $client = Client::create([
            'societe_id' => $societe?->id,
            'prefix_id' => $prefix->id,
            'nomcomplet_client' => $row['nom_cli'],
            'nom_cli' => $row['nom_cli'],
            'date_naiss_cli' => Date::excelToDateTimeObject($row['date_nais_cli'])->format('Y-m-d'),
            'ref_cli' => $row['ref_cli'],
            'tel_cli' => $row['tel_cli'],
            'type_cli' => 'associate',
            'email' => $row['email_cli'],
            'created_at' => Date::excelToDateTimeObject($row['date_creation_cli'])->format('Y-m-d'),
            'client_anonyme_cli' => false
        ]);

        ConventionAssocie::create([
            'client_id' => $client->id,
            'date' => now(),
            'amount_max' => 25000000,
            'start_date' => now(),
            'end_date' => now()->endOfYear(),
        ]);

        return null;
    }
}
