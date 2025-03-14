<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CentreSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Centre::factory(2)->create();
    }
}
