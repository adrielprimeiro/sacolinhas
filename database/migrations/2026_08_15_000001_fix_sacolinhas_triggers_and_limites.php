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
        // 1. Drop e recria as triggers na tabela sacolinhas para ignorar itens já convertidos em pedido (status = 'pedido')
        DB::unprepared("DROP TRIGGER IF EXISTS `tr_sacolinhas_insert_limites`");
        DB::unprepared("
            CREATE TRIGGER `tr_sacolinhas_insert_limites` AFTER INSERT ON `sacolinhas`
            FOR EACH ROW
            BEGIN
                DECLARE total_ativo DECIMAL(10,2);
                
                SELECT COALESCE(SUM(price * quantity), 0.00) INTO total_ativo
                FROM sacolinhas
                WHERE user_id = NEW.user_id AND status != 'pedido';
                
                INSERT INTO cliente_limites (user_id, limite_credito, limite_utilizado, limite_disponivel, ativo, data_ultimo_ajuste, motivo_ultimo_ajuste, created_at, updated_at)
                VALUES (NEW.user_id, 100.00, total_ativo, 100.00 - total_ativo, 1, NOW(), 'Item Adicionado', NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    limite_utilizado = total_ativo,
                    limite_disponivel = limite_credito - total_ativo,
                    data_ultimo_ajuste = NOW(),
                    motivo_ultimo_ajuste = 'Item Adicionado',
                    updated_at = NOW();
            END
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS `tr_sacolinhas_update_limites`");
        DB::unprepared("
            CREATE TRIGGER `tr_sacolinhas_update_limites` AFTER UPDATE ON `sacolinhas`
            FOR EACH ROW
            BEGIN
                DECLARE total_ativo DECIMAL(10,2);
                
                IF OLD.price <> NEW.price OR OLD.quantity <> NEW.quantity OR OLD.status <> NEW.status THEN
                    SELECT COALESCE(SUM(price * quantity), 0.00) INTO total_ativo
                    FROM sacolinhas
                    WHERE user_id = NEW.user_id AND status != 'pedido';
                    
                    UPDATE cliente_limites SET
                        limite_utilizado = total_ativo,
                        limite_disponivel = limite_credito - total_ativo,
                        data_ultimo_ajuste = NOW(),
                        motivo_ultimo_ajuste = 'Item Alterado',
                        updated_at = NOW()
                    WHERE user_id = NEW.user_id;
                END IF;
            END
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS `tr_sacolinhas_delete_limites`");
        DB::unprepared("
            CREATE TRIGGER `tr_sacolinhas_delete_limites` AFTER DELETE ON `sacolinhas`
            FOR EACH ROW
            BEGIN
                DECLARE total_ativo DECIMAL(10,2);
                
                SELECT COALESCE(SUM(price * quantity), 0.00) INTO total_ativo
                FROM sacolinhas
                WHERE user_id = OLD.user_id AND status != 'pedido';
                
                UPDATE cliente_limites SET
                    limite_utilizado = total_ativo,
                    limite_disponivel = limite_credito - total_ativo,
                    data_ultimo_ajuste = NOW(),
                    motivo_ultimo_ajuste = 'Item Removido',
                    updated_at = NOW()
                WHERE user_id = OLD.user_id;
            END
        ");

        // 2. Recalcular retroativamente cliente_limites para todos os clientes
        $limites = DB::table('cliente_limites')->get();
        foreach ($limites as $row) {
            $totalAtivo = DB::table('sacolinhas')
                ->where('user_id', $row->user_id)
                ->where('status', '!=', 'pedido')
                ->sum(DB::raw('price * quantity'));

            $limiteCredito = (float) $row->limite_credito;
            $utilizado = round((float) $totalAtivo, 2);
            $disponivel = round($limiteCredito - $utilizado, 2);

            DB::table('cliente_limites')
                ->where('user_id', $row->user_id)
                ->update([
                    'limite_utilizado' => $utilizado,
                    'limite_disponivel' => $disponivel,
                    'data_ultimo_ajuste' => now(),
                    'motivo_ultimo_ajuste' => 'Recálculo Geral de Limite',
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter triggers se necessário
    }
};
