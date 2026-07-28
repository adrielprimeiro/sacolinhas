<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixClubePontosMensalidade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clube:fix-pontos-mensalidade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula os pontos de todos os clientes no Clube (forçando a atualização das views e executando a procedure)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Atualizando as Views de pontuação (caso a migration tenha falhado)...');

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

        $this->info('Views atualizadas. Buscando mensalidades pagas para recalcular...');

        $mensalidades = DB::table('clube_mensalidades')
            ->select('user_id', 'competencia_ano', 'competencia_mes')
            ->where('status_pagamento', 'pago')
            ->distinct()
            ->get();

        $bar = $this->output->createProgressBar(count($mensalidades));

        foreach ($mensalidades as $m) {
            $mesAno = sprintf('%04d-%02d', $m->competencia_ano, $m->competencia_mes);
            DB::unprepared("CALL atualizar_pontuacoes_user({$m->user_id}, '{$mesAno}')");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Pontuações recalculadas com sucesso!');
    }
}
