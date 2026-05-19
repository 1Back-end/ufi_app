<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\TypePrestation;

class TypePrestationSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Models\User::first(); // ou admin par défaut

        foreach (TypePrestation::toArray() as $id => $name) {

            DB::table('type_prestations')->updateOrInsert(
                [
                    'id' => $id
                ],
                [
                    'name' => $name,

                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
