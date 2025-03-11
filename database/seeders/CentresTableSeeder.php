<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Centre;
class CentresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Centre::insert([
            [
                'nom_centre' => 'Centre de Santé A',
                'tel_centre' => '123456789',
                'numero_contribuable_centre' => '1234',
                'registre_com_centre' => 'ABC123',
                'fax_centre' => '987654321',
                'email_centre' => 'contact@centresantéA.com',
                'numero_autorisation_centre' => 'A123',
                'logo_centre' => 'logoA.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_centre' => 'Centre de Santé B',
                'tel_centre' => '987654321',
                'numero_contribuable_centre' => '5678',
                'registre_com_centre' => 'XYZ567',
                'fax_centre' => '123456789',
                'email_centre' => 'contact@centresantéB.com',
                'numero_autorisation_centre' => 'B123',
                'logo_centre' => 'logoB.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    
        //
    }
}
