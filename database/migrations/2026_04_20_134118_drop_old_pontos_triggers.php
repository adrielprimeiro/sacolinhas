<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\DB::getDriverName() === 'sqlite') {
            return;
        }

        // 1. Remover triggers que atualizam pontos automaticamente via BD ao mexer em itens do pedido
        \DB::unprepared('DROP TRIGGER IF EXISTS trg_items_pedido_ai');
        \DB::unprepared('DROP TRIGGER IF EXISTS trg_items_pedido_au');
        \DB::unprepared('DROP TRIGGER IF EXISTS trg_items_pedido_ad');

        // 2. Modificar a procedure atualizar_pontuacoes_user para NÃO sobrescrever pontos_itens
        // (ela continuará cuidando de mensalidade e bônus de grupo, mas ignorará os pontos de itens que agora serão PHP)
        \DB::unprepared("DROP PROCEDURE IF EXISTS atualizar_pontuacoes_user");
        \DB::unprepared("
            CREATE PROCEDURE `atualizar_pontuacoes_user`(
                IN p_user_id BIGINT UNSIGNED,
                IN p_mes_ano VARCHAR(7)
            )
            BEGIN
                DECLARE v_pontos_mensalidade DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_pontos_desafios DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_pontos_bonus_grupo DECIMAL(10,2) DEFAULT 0.00;
                DECLARE v_grupo_id BIGINT UNSIGNED DEFAULT NULL;

                -- Busca pontos de mensalidade
                SELECT COALESCE(SUM(pontos_mensalidade), 0) INTO v_pontos_mensalidade
                FROM vw_pontos_mensalidade_user WHERE user_id = p_user_id AND mes_ano = p_mes_ano;
                
                -- Busca pontos de desafios
                SELECT COALESCE(pontos_desafios, 0) INTO v_pontos_desafios
                FROM vw_pontos_desafios_user WHERE user_id = p_user_id AND mes_ano = p_mes_ano;
                
                -- Busca bônus de grupo (50% do total do grupo)
                SELECT gm.grupo_id INTO v_grupo_id FROM grupo_membros gm WHERE gm.user_id = p_user_id LIMIT 1;
                IF v_grupo_id IS NOT NULL THEN
                    SELECT COALESCE(total, 0) * 0.5 INTO v_pontos_bonus_grupo 
                    FROM pontuacoes_grupos WHERE grupo_id = v_grupo_id AND mes_ano = p_mes_ano;
                END IF;
                
                -- Atualiza a tabela, mas NÃO inclui pontos_itens no UPDATE para não zerar o que o PHP fizer
                INSERT INTO pontuacoes_clientes (user_id, mes_ano, pontos_mensalidade, pontos_desafios, pontos_bonus_grupo)
                VALUES (p_user_id, p_mes_ano, v_pontos_mensalidade, v_pontos_desafios, v_pontos_bonus_grupo)
                ON DUPLICATE KEY UPDATE
                    pontos_mensalidade = VALUES(pontos_mensalidade),
                    pontos_desafios = VALUES(pontos_desafios),
                    pontos_bonus_grupo = VALUES(pontos_bonus_grupo);
            END
        ");

        // 3. Modificar a procedure do grupo também para não sobrescrever pontos_itens do grupo
        \DB::unprepared("DROP PROCEDURE IF EXISTS atualizar_pontuacoes_grupo");
        \DB::unprepared("
            CREATE PROCEDURE `atualizar_pontuacoes_grupo`(
                IN p_grupo_id BIGINT UNSIGNED, 
                IN p_mes_ano VARCHAR(7)
            )
            BEGIN
                DECLARE v_pontos_mensalidades DECIMAL(10,2) DEFAULT 0.00;
                DECLARE done INT DEFAULT FALSE;
                DECLARE v_user_id BIGINT UNSIGNED;
                DECLARE cur CURSOR FOR SELECT DISTINCT gm.user_id FROM grupo_membros gm WHERE gm.grupo_id = p_grupo_id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

                -- Soma mensalidades do grupo
                SELECT COALESCE(SUM(pontos_mensalidades), 0) INTO v_pontos_mensalidades
                FROM vw_pontos_mensalidade_grupo WHERE grupo_id = p_grupo_id AND mes_ano = p_mes_ano;

                -- Insere/Atualiza sem tocar nos pontos_itens do grupo
                INSERT INTO pontuacoes_grupos (grupo_id, mes_ano, pontos_mensalidades)
                VALUES (p_grupo_id, p_mes_ano, v_pontos_mensalidades)
                ON DUPLICATE KEY UPDATE
                    pontos_mensalidades = VALUES(pontos_mensalidades);

                -- Recalcula pontos dos membros para atualizar o bônus de grupo de cada um
                OPEN cur;
                read_loop: LOOP
                    FETCH cur INTO v_user_id;
                    IF done THEN LEAVE read_loop; END IF;
                    CALL atualizar_pontuacoes_user(v_user_id, p_mes_ano);
                END LOOP;
                CLOSE cur;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Como estamos lidando com triggers e procedures complexas de um dump externo, 
        // o 'rollback' exigiria restaurar o código original exato, o que é arriscado via migration.
    }
};
