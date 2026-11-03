<?php

namespace Database\Seeders;

use App\Models\ReportRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportRecipientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firstUser = User::first();
        $createdById = $firstUser ? $firstUser->id : 1;

        $emails = [
            'nguessistephane@gmail.com',
            'devxs360@gmail.com'
        ];

        foreach ($emails as $email) {
            ReportRecipient::updateOrCreate(
                ['email' => $email],
                [
                    'created_by' => $createdById,
                    'updated_by' => null,
                ]
            );
        }
    }
}
