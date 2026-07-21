<?php

namespace Database\Seeders;

use App\Models\Fournisseurs;
use App\Models\User;
use Illuminate\Database\Seeder;

class FournisseurDiversSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::first()?->id ?? 1;

        Fournisseurs::firstOrCreate(
            ['company_name' => 'DIVERS'],
            [
                'full_name'                    => 'FOURNISSEUR DIVERS',
                'company_name'                 => 'DIVERS',
                'address'                      => 'N/A',
                'phone_number'                 => '000000000',
                'email'                        => 'divers@pharmacie.local',
                'city'                         => 'N/A',
                'country'                      => 'Cameroun',
                'contact_person'               => 'Service Achats Divers',
                'is_active'                    => true,
                'created_by'                   => $adminId,
                'updated_by'                   => $adminId,
            ]
        );
    }
}
