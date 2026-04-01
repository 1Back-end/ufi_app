<?php

namespace Database\Seeders;

use App\Models\FactureAssuranceByCentre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FactureAssuranceByCentreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        // Facture pour le centre 2
        FactureAssuranceByCentre::updateOrCreate(
            ['centre_id' => 2], // condition pour trouver l'enregistrement
            [
                'object_of_facture_assurance' => 'Consultations et examens de vos assurés',
                'mode_of_payment' => 'par chèque ou virement',
                'compte_or_payment' => 'COMMERCIAL BANK CAMEROUN (CBC)',
                'number_for_compte' => '1234567890',
                'text_of_remerciement' => 'Nous vous remercions pour votre confiance',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );

        // Facture pour le centre 1 (uniquement l'objet)
        FactureAssuranceByCentre::updateOrCreate(
            ['centre_id' => 1], // condition pour trouver l'enregistrement
            [
                'object_of_facture_assurance' => 'Analyses médicales de vos assurés',
                'mode_of_payment' => null,
                'compte_or_payment' => null,
                'number_for_compte' => null,
                'text_of_remerciement' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]
        );
    }
}
