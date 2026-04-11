<?php
namespace App\Domains\Clube\Repositories;

use Illuminate\Support\Facades\DB;

class ClubeIndicadoresRepository
{
    public function upsertPedidosETaxa(int $userId): void
    {
        $sql = "
            INSERT INTO cliente_clube_indicadores (
                user_id,
                pedidos_concluidos, pedidos_total,
                itens_cancelados, itens_devolvidos, itens_finalizados,
                taxa_cancel_devol_percent,
                periodo_inicio, periodo_fim,
                atualizado_em
            )
            SELECT
                ?,
                COALESCE(ped.pedidos_concluidos, 0),
                COALESCE(ped.pedidos_total, 0),
                COALESCE(tax.itens_cancelados, 0),
                COALESCE(tax.itens_devolvidos, 0),
                COALESCE(tax.itens_finalizados, 0),
                COALESCE(tax.taxa_cancel_devol_percent, 0.00),
                (CURRENT_DATE - INTERVAL 6 MONTH),
                CURRENT_DATE,
                CURRENT_TIMESTAMP
            FROM (SELECT 1) dummy
            LEFT JOIN (
                SELECT
                    SUM(p.status_pedido = 'concluido') AS pedidos_concluidos,
                    COUNT(*) AS pedidos_total
                FROM pedidos p
                WHERE p.user_id = ?
            ) ped ON 1=1
            LEFT JOIN (
                SELECT
                    SUM(ip.status_item = 'cancelado') AS itens_cancelados,
                    SUM(ip.status_item = 'devolvido') AS itens_devolvidos,
                    SUM(ip.status_item = 'ativo') AS itens_finalizados,
                    ROUND(
                        (SUM(ip.status_item IN ('cancelado','devolvido')) / NULLIF(COUNT(ip.id),0)) * 100
                    , 2) AS taxa_cancel_devol_percent
                FROM pedidos p
                JOIN items_pedido ip ON ip.pedido_id = p.id
                WHERE p.user_id = ?
                  AND p.data_pedido >= (CURRENT_DATE - INTERVAL 6 MONTH)
            ) tax ON 1=1
            ON DUPLICATE KEY UPDATE
                pedidos_concluidos = VALUES(pedidos_concluidos),
                pedidos_total = VALUES(pedidos_total),
                itens_cancelados = VALUES(itens_cancelados),
                itens_devolvidos = VALUES(itens_devolvidos),
                itens_finalizados = VALUES(itens_finalizados),
                taxa_cancel_devol_percent = VALUES(taxa_cancel_devol_percent),
                periodo_inicio = VALUES(periodo_inicio),
                periodo_fim = VALUES(periodo_fim),
                atualizado_em = CURRENT_TIMESTAMP
        ";

        DB::statement($sql, [$userId, $userId, $userId]);
    }

    public function atualizarMensalidadeStatus(int $userId): void
    {
        $sql = "
            UPDATE cliente_clube_indicadores c
            LEFT JOIN (
                SELECT
                    a.user_id,
                    CASE
                        WHEN MAX(a.status='ativa' AND (a.fim_em IS NULL OR a.fim_em >= CURRENT_DATE)) = 1
                            THEN 'ativa'
                        ELSE 'inativa'
                    END AS mensalidade_status
                FROM clube_assinaturas a
                WHERE a.user_id = ?
                GROUP BY a.user_id
            ) x ON x.user_id = c.user_id
            SET c.mensalidade_status = COALESCE(x.mensalidade_status, 'inativa'),
                c.atualizado_em = CURRENT_TIMESTAMP
            WHERE c.user_id = ?
        ";

        DB::statement($sql, [$userId, $userId]);
    }

    public function atualizarMensalidadesTotal(int $userId): void
    {
        $sql = "
            UPDATE cliente_clube_indicadores c
            LEFT JOIN (
                SELECT
                    user_id,
                    COUNT(*) AS mensalidades_total
                FROM clube_mensalidades
                WHERE user_id = ?
                  AND status_pagamento = 'pago'
                GROUP BY user_id
            ) m ON m.user_id = c.user_id
            SET c.mensalidades_total = COALESCE(m.mensalidades_total, 0),
                c.atualizado_em = CURRENT_TIMESTAMP
            WHERE c.user_id = ?
        ";

        DB::statement($sql, [$userId, $userId]);
    }

    public function atualizarMensalidadesSequencia(int $userId): void
    {
        // MySQL 8.0 - streak só conta se estiver em dia
        $sql = "
            WITH params AS (
                SELECT (YEAR(CURRENT_DATE) * 12 + MONTH(CURRENT_DATE)) AS comp_atual
            ),
            pagos AS (
                SELECT
                    m.user_id,
                    (m.competencia_ano * 12 + m.competencia_mes) AS comp
                FROM clube_mensalidades m
                WHERE m.user_id = ?
                  AND m.status_pagamento = 'pago'
            ),
            tem_mes_atual AS (
                SELECT
                    p.user_id,
                    1 AS ok
                FROM pagos p
                JOIN params x ON p.comp = x.comp_atual
                GROUP BY p.user_id
            ),
            ordenado AS (
                SELECT
                    p.user_id,
                    p.comp,
                    ROW_NUMBER() OVER (PARTITION BY p.user_id ORDER BY p.comp DESC) AS rn
                FROM pagos p
                JOIN tem_mes_atual t ON t.user_id = p.user_id
            ),
            grupos AS (
                SELECT
                    user_id,
                    comp,
                    rn,
                    (comp + rn) AS grp
                FROM ordenado
            ),
            streak AS (
                SELECT
                    g.user_id,
                    COUNT(*) AS mensalidades_sequencia
                FROM grupos g
                WHERE g.grp = (
                    SELECT (g2.comp + g2.rn)
                    FROM grupos g2
                    WHERE g2.user_id = g.user_id
                    ORDER BY g2.comp DESC
                    LIMIT 1
                )
                GROUP BY g.user_id
            )
            UPDATE cliente_clube_indicadores c
            LEFT JOIN streak s ON s.user_id = c.user_id
            SET c.mensalidades_sequencia = COALESCE(s.mensalidades_sequencia, 0),
                c.atualizado_em = CURRENT_TIMESTAMP
            WHERE c.user_id = ?
        ";

        DB::statement($sql, [$userId, $userId]);
    }
}