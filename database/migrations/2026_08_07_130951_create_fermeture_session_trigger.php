<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS trigger_fermer_session_caisse_minuit;

            CREATE TRIGGER trigger_fermer_session_caisse_minuit
            BEFORE UPDATE ON session_caisse
            FOR EACH ROW
            BEGIN
                -- Vérifie si la session est OUVERTE ou EN_PAUSE,
                -- si nous sommes un jour différent de l\'ouverture (passage après minuit),
                -- et si le current_sold est exactement égal à 0.
                IF (NEW.etat IN ("OUVERTE", "EN_PAUSE"))
                   AND (DATE(NEW.ouverture_ts) < CURDATE())
                   AND (NEW.current_sold = 0) THEN

                    -- 1. Fermeture de la session de caisse
                    SET NEW.etat = "FERMEE";
                    SET NEW.fermeture_ts = NOW();

                    -- 2. Mise à jour de la caisse associée (position à close)
                    UPDATE caisses
                    SET position = "close",
                        updated_at = NOW()
                    WHERE id = NEW.caisse_id;

                END IF;
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_fermer_session_caisse_minuit;');
    }
};
