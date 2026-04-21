<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PontuacoesService
{
    /**
     * Adiciona ou subtrai pontos de itens para um usuário no mês atual.
     * 
     * @param int $userId
     * @param float $points Pontos a somar (pode ser negativo)
     */
    public static function updateItemPoints(int $userId, float $points)
    {
        $mesAno = date('Y-m');

        // Busca o grupo do usuário
        $grupoId = DB::table('grupo_membros')
            ->where('user_id', $userId)
            ->value('grupo_id');

        DB::transaction(function () use ($userId, $mesAno, $points, $grupoId) {
            // 1. Garantir que o registro do cliente existe
            DB::table('pontuacoes_clientes')->updateOrInsert(
                ['user_id' => $userId, 'mes_ano' => $mesAno],
                ['pontos_itens' => DB::raw('COALESCE(pontos_itens, 0)')]
            );
            
            // 2. Incrementar/Decrementar pontos_itens do cliente
            DB::table('pontuacoes_clientes')
                ->where('user_id', $userId)
                ->where('mes_ano', $mesAno)
                ->increment('pontos_itens', $points);

            // 3. Se tiver grupo, atualizar pontos do grupo também
            if ($grupoId) {
                DB::table('pontuacoes_grupos')->updateOrInsert(
                    ['grupo_id' => $grupoId, 'mes_ano' => $mesAno],
                    ['pontos_itens' => DB::raw('COALESCE(pontos_itens, 0)')]
                );

                DB::table('pontuacoes_grupos')
                    ->where('grupo_id', $grupoId)
                    ->where('mes_ano', $mesAno)
                    ->increment('pontos_itens', $points);
                
                // 4. Rodar procedure de sincronização do grupo (para atualizar bônus dos outros membros)
                // Usamos a procedure que já existe no banco
                DB::unprepared("CALL atualizar_pontuacoes_grupo($grupoId, '$mesAno')");
            } else {
                // Se não tem grupo, apenas roda a do user para garantir outros bônus se existirem
                DB::unprepared("CALL atualizar_pontuacoes_user($userId, '$mesAno')");
            }
        });
    }

    /**
     * Registra um ponto de desafio para o usuário e atualiza as pontuações.
     */
    public static function addChallengePoints(int $userId, float $points, string $challengeName, ?string $mesAno = null)
    {
        $mesAno = $mesAno ?: date('Y-m');

        DB::transaction(function () use ($userId, $mesAno, $points, $challengeName) {
            // 1. Inserir o registro do desafio
            DB::table('pontos_desafios')->insert([
                'user_id' => $userId,
                'mes_ano' => $mesAno,
                'pontos' => $points,
                'desafio_nome' => $challengeName,
                'created_at' => now()
            ]);

            // 2. Chamar a procedure que recalcula tudo (mensalidade, desafios, bônus de grupo)
            // A procedure 'atualizar_pontuacoes_user' já busca da 'vw_pontos_desafios_user'
            DB::unprepared("CALL atualizar_pontuacoes_user($userId, '$mesAno')");

            // 3. Se o usuário estiver em um grupo, precisamos atualizar o grupo também
            $grupoId = DB::table('grupo_membros')
                ->where('user_id', $userId)
                ->value('grupo_id');

            if ($grupoId) {
                DB::unprepared("CALL atualizar_pontuacoes_grupo($grupoId, '$mesAno')");
            }
        });
    }
}

