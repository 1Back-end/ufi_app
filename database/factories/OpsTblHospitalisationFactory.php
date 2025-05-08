<?php

namespace Database\Factories;
use App\Models\OpsTblHospitalisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OpsTblHospitalisation>
 */
class OpsTblHospitalisationFactory extends Factory
{
    protected $model = OpsTblHospitalisation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prices = [1000, 2500, 5000, 7500, 10000];
        // Tableau des noms d'hospitalisations
        $hospitalisationNames = [
            'Chirurgie cardiaque',
            'Rééducation fonctionnelle',
            'Neurologie',
            'Oncologie',
            'Pédiatrie',
            'Gynécologie',
            'Orthopédie',
            'Chirurgie esthétique',
            'Médecine générale',
            'Hématologie'
        ];

        return [
            'name' => $this->faker->randomElement($hospitalisationNames),
            'pu' => $this->faker->randomElement($prices), // Choisir un prix au hasard dans le tableau des prix
            'description' => $this->faker->sentence(),
            'created_by' => 2, // L'utilisateur qui a créé
            'updated_by' => 2, // L'utilisateur qui a mis à jour
            'is_deleted' => $this->faker->boolean(10), // 10% de chance que ce soit supprimé
        ];

    }
}
