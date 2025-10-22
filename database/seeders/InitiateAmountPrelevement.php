<?php

namespace Database\Seeders;

use App\Models\Prestation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitiateAmountPrelevement extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prestationPC = Prestation::query()
            ->with(['priseCharge'])
            ->whereNotNull('prise_charge_id')
            ->get();

        foreach ($prestationPC as $prestation) {
            $facture = $prestation->factures->where('type', 2)->first();
            if ($facture) {
                $facture->update([
                    'amount_prelevement_pc' => ($facture->amount_prelevement * $prestation->priseCharge->taux_pc) / 100,
                ]);
            }
        }
    }
}
