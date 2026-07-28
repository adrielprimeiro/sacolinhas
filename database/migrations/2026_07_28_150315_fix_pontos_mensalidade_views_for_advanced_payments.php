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
        // Fix for User view
        DB::unprepared("
            CREATE OR REPLACE VIEW vw_pontos_mensalidade_user AS 
            SELECT 
                cm.user_id,
                CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano,
                CASE 
                    WHEN (
                        cm.status_pagamento = 'pago' 
                        AND DATE(cm.pago_em) <= STR_TO_DATE(CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0'), '-25'), '%Y-%m-%d')
                    ) THEN 100.00 
                    ELSE 0.00 
                END AS pontos_mensalidade 
            FROM clube_mensalidades cm
        ");

        // Fix for Group in day view
        DB::unprepared("
            CREATE OR REPLACE VIEW vw_grupo_em_dia AS 
            WITH meses AS (
                SELECT DISTINCT CONCAT(competencia_ano, '-', LPAD(competencia_mes, 2, '0')) AS mes_ano 
                FROM clube_mensalidades
            ) 
            SELECT 
                g.id AS grupo_id,
                m.mes_ano AS mes_ano,
                COUNT(DISTINCT gm.user_id) AS num_membros_total,
                SUM(p.pago_em_dia) AS num_pagos_dia,
                CASE WHEN (SUM(p.pago_em_dia) = COUNT(DISTINCT gm.user_id)) THEN 1 ELSE 0 END AS grupo_100_em_dia 
            FROM grupos g 
            JOIN grupo_membros gm ON g.id = gm.grupo_id 
            JOIN meses m 
            LEFT JOIN (
                SELECT 
                    cm.user_id,
                    CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano,
                    CASE 
                        WHEN (
                            cm.status_pagamento = 'pago' 
                            AND DATE(cm.pago_em) <= STR_TO_DATE(CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0'), '-25'), '%Y-%m-%d')
                        ) THEN 1 
                        ELSE 0 
                    END AS pago_em_dia 
                FROM clube_mensalidades cm
            ) p ON gm.user_id = p.user_id AND m.mes_ano = p.mes_ano 
            GROUP BY g.id, m.mes_ano
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original user view
        DB::unprepared("
            CREATE OR REPLACE VIEW vw_pontos_mensalidade_user AS 
            SELECT 
                cm.user_id,
                CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano,
                CASE 
                    WHEN (cm.status_pagamento = 'pago' AND DAYOFMONTH(cm.pago_em) <= 25) THEN 100.00 
                    ELSE 0.00 
                END AS pontos_mensalidade 
            FROM clube_mensalidades cm
        ");

        // Revert to original group in day view
        DB::unprepared("
            CREATE OR REPLACE VIEW vw_grupo_em_dia AS 
            WITH meses AS (
                SELECT DISTINCT CONCAT(competencia_ano, '-', LPAD(competencia_mes, 2, '0')) AS mes_ano 
                FROM clube_mensalidades
            ) 
            SELECT 
                g.id AS grupo_id,
                m.mes_ano AS mes_ano,
                COUNT(DISTINCT gm.user_id) AS num_membros_total,
                SUM(p.pago_em_dia) AS num_pagos_dia,
                CASE WHEN (SUM(p.pago_em_dia) = COUNT(DISTINCT gm.user_id)) THEN 1 ELSE 0 END AS grupo_100_em_dia 
            FROM grupos g 
            JOIN grupo_membros gm ON g.id = gm.grupo_id 
            JOIN meses m 
            LEFT JOIN (
                SELECT 
                    cm.user_id,
                    CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano,
                    CASE 
                        WHEN (cm.status_pagamento = 'pago' AND DAYOFMONTH(cm.pago_em) <= 25) THEN 1 
                        ELSE 0 
                    END AS pago_em_dia 
                FROM clube_mensalidades cm
            ) p ON gm.user_id = p.user_id AND m.mes_ano = p.mes_ano 
            GROUP BY g.id, m.mes_ano
        ");
    }
};
