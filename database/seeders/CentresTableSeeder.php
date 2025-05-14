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
                'nom' => 'Centre de Santé A',
                'tel' => '123456789',
                'numero_contribuable' => '1234',
                'registre_com' => 'ABC123',
                'fax' => '987654321',
                'email' => 'contact@centresantéA.com',
                'numero_autorisation' => 'A123',
                'logo' => 'logoA.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Centre de Santé B',
                'tel' => '987654321',
                'numero_contribuable' => '5678',
                'registre_com' => 'XYZ567',
                'fax' => '123456789',
                'email' => 'contact@centresantéB.com',
                'numero_autorisation' => 'B123',
                'logo' => 'logoB.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        //
    }
}
