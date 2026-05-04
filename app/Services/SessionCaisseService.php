<?php

namespace App\Services;

use App\Models\SessionCaisse;
use App\Models\Caisse;
use App\Models\TransfertFondsTampon;
use Carbon\Carbon;

class SessionCaisseService
{
    /**
     * 🔥 Fermeture automatique des sessions de caisse
     */
    public function autoClose($authId = null)
    {
        $sessions = SessionCaisse::whereNull('fermeture_ts')
            ->where('etat', 'OUVERTE')
            ->get();

        foreach ($sessions as $session) {

            $caisse = Caisse::where('id', $session->caisse_id)->first();

            $grandeCaisse = Caisse::where('centre_id', $session->centre_id)
                ->where('is_primary', true)
                ->first();

            if (!$caisse || !$grandeCaisse) {
                continue;
            }

            $montant = $session->current_sold ?? 0;
            $creatorId = $session->created_by ?? $session->user_id;


            if ($montant > 0) {

                TransfertFondsTampon::create([
                    'caisse_depart_id' => $caisse->id,
                    'caisse_reception_id' => $grandeCaisse->id,
                    'session_id' => $session->id,
                    'status' => 'pending',
                    'montant_send' => $montant,
                    'send_by' => $creatorId,
                    'created_by' => $creatorId,
                    'centre_id' => $session->centre_id,
                    'type' => 'debit',
                    'validated_by' => $authId ?? $creatorId,
                    'validated_at' => now(),
                ]);
            }

            // 🔥 TOUJOURS FERMER LA SESSION
            $session->update([
                'fermeture_ts' => now(),
                'fonds_fermeture' => $montant,
                'fonds_fermeture_exactly' => $montant,
                'etat' => 'FERMEE',
                'current_sold' => 0,
                'updated_by' => $authId ?? $creatorId
            ]);

            // 🔥 TOUJOURS FERMER LA CAISSE
            $caisse->update([
                'position' => 'close',
                'updated_by' => $authId ?? $creatorId
            ]);
        }

        return $sessions->count();
    }
}
