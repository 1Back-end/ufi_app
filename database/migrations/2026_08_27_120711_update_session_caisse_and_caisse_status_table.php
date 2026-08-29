<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Activer le planificateur d'événements MySQL
        DB::statement('SET GLOBAL event_scheduler = ON;');
        DB::unprepared('DROP EVENT IF EXISTS evt_auto_close_expired_sessions;');

        // 2. Créer l'événement pour automatiser la vérification chaque minute
        DB::unprepared("
            CREATE EVENT evt_auto_close_expired_sessions
            ON SCHEDULE EVERY 1 MINUTE
            DO
            BEGIN
                -- A. Insertion des transferts uniquement pour celles qui ont un solde > 0 et pas encore de transfert
                INSERT INTO transfert_fonds_tampons (
                    code,
                    caisse_depart_id,
                    session_id,
                    status,
                    montant_send,
                    send_by,
                    created_by,
                    centre_id,
                    type,
                    small_change,
                    created_at,
                    updated_at
                )
                SELECT
                    CONCAT('TRF-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-', FLOOR(1000 + RAND() * 9000)),
                    s.caisse_id,
                    s.id,
                    'pending',
                    CAST(REPLACE(REPLACE(COALESCE(s.current_sold, '0'), ' ', ''), ',', '') AS DECIMAL(15,2)),
                    COALESCE(s.user_id, 1),
                    COALESCE(s.user_id, 1),
                    s.centre_id,
                    'debit',
                    0,
                    NOW(),
                    NOW()
                FROM session_caisse s
                WHERE CAST(REPLACE(REPLACE(COALESCE(s.current_sold, '0'), ' ', ''), ',', '') AS DECIMAL(15,2)) > 0
                  AND DATE(s.created_at) < CURDATE()
                  AND NOT EXISTS (
                      SELECT 1 FROM transfert_fonds_tampons t WHERE t.session_id = s.id
                  );

                -- B. Mettre la position des caisses à 'close' pour toutes les sessions antérieures
                UPDATE caisses c
                JOIN session_caisse s ON s.caisse_id = c.id
                SET c.position = 'close'
                WHERE DATE(s.created_at) < CURDATE();

                -- C. Fermer (mettre l'état à 'FERMEE') toutes les sessions antérieures à aujourd'hui
                UPDATE session_caisse
                SET etat = 'FERMEE',
                    fermeture_ts = COALESCE(fermeture_ts, NOW())
                WHERE DATE(created_at) < CURDATE();

                -- D. Remettre TOUS les soldes à 0 pour ABSOLUMENT TOUTES les sessions antérieures (avec ou sans transfert)
                UPDATE session_caisse
                SET solde = 0,
                    current_sold = 0,
                    sold_without_small_change = 0
                WHERE DATE(created_at) < CURDATE();
            END
        ");

        // 3. Exécuter la même logique immédiatement lors de la migration pour nettoyer tout l'existant
        DB::statement("
            INSERT INTO transfert_fonds_tampons (
                code,
                caisse_depart_id,
                session_id,
                status,
                montant_send,
                send_by,
                created_by,
                centre_id,
                type,
                small_change,
                created_at,
                updated_at
            )
            SELECT
                CONCAT('TRF-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-', FLOOR(1000 + RAND() * 9000)),
                s.caisse_id,
                s.id,
                'pending',
                CAST(REPLACE(REPLACE(COALESCE(s.current_sold, '0'), ' ', ''), ',', '') AS DECIMAL(15,2)),
                COALESCE(s.user_id, 1),
                COALESCE(s.user_id, 1),
                s.centre_id,
                'debit',
                0,
                NOW(),
                NOW()
            FROM session_caisse s
            WHERE CAST(REPLACE(REPLACE(COALESCE(s.current_sold, '0'), ' ', ''), ',', '') AS DECIMAL(15,2)) > 0
              AND DATE(s.created_at) < CURDATE()
              AND NOT EXISTS (
                  SELECT 1 FROM transfert_fonds_tampons t WHERE t.session_id = s.id
              )
        ");

        // Mettre à jour les caisses à 'close' pour l'existant
        DB::statement("
            UPDATE caisses c
            JOIN session_caisse s ON s.caisse_id = c.id
            SET c.position = 'close'
            WHERE DATE(s.created_at) < CURDATE();
        ");

        // Fermer les sessions immédiatement pour l'existant
        DB::statement("
            UPDATE session_caisse
            SET etat = 'FERMEE',
                fermeture_ts = COALESCE(fermeture_ts, NOW())
            WHERE DATE(created_at) < CURDATE();
        ");

        // Remettre TOUS les soldes à 0 pour l'existant sans dépendre de la table des transferts
        DB::statement("
            UPDATE session_caisse
            SET solde = 0,
                current_sold = 0,
                sold_without_small_change = 0
            WHERE DATE(created_at) < CURDATE();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP EVENT IF EXISTS evt_auto_close_expired_sessions;');
    }
};
