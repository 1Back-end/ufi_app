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
use Database\Seeders\QuotationSeeder;
use Database\Seeders\OpsTblHospitalisationSeeder;
use Database\Seeders\GroupProductSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\UniteProduitSeeder;
use Database\Seeders\TypeconsultationSeeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InitBDForAllDataSeeder::class,
            TypeconsultationSeeder::class,
            OpsTblHospitalisationSeeder::class,
            FournisseurDiversSeeder::class,
            SexesSeeder::class,
            PrefixSeeder::class,
            StatusFamilialeSeeder::class,
            TypeDocumentSeeder::class,
            CountriesTableSeeder::class,
        ]);
    }

}
