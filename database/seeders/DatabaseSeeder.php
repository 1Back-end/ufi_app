<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\DroitSeeder;
use Database\Seeders\ProfileSeeder;
use Database\Seeders\ProfileDroitSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\UserCentreTableSeeder;
use Database\Seeders\CentresTableSeeder;
use Database\Seeders\HopitalsTableSeeder;
use Database\Seeders\SpecialitesTableSeeder;
use Database\Seeders\TitresTableSeeder;
use Database\Seeders\ServiceHopitalsTableSeeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'nom_utilisateur' => 'Test User',
            'email' => 'test@example.com',
        ]);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            DroitSeeder::class,
            ProfileSeeder::class,
            ProfileDroitSeeder::class,
            UsersTableSeeder::class,
                // UserCentreTableSeeder::class,
            CentresTableSeeder::class,
            HopitalsTableSeeder::class,
            SpecialitesTableSeeder::class,
            TitresTableSeeder::class,
            ServiceHopitalsTableSeeder::class,
        ]);

        $this->call([
            SexesSeeder::class,
            PrefixSeeder::class,
            StatusFamilialeSeeder::class,
            TypeDocumentSeeder::class,
            SocieteSeeder::class,
            ClientSeeder::class
        ]);
    }

}
