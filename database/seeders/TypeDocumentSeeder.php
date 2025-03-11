<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TypeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\TypeDocument::factory()->create([
            'description_typedoc' => 'CNI',
        ]);

        \App\Models\TypeDocument::factory()->create([
            'description_typedoc' => 'Passeport',
        ]);

        \App\Models\TypeDocument::factory()->create([
            'description_typedoc' => 'Autre',
        ]);
    }
}
