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
        // 1. tr_items_pedido_ai_recalc_pedido_total
        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_ai_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_ai_recalc_pedido_total
            AFTER INSERT ON items_pedido
            FOR EACH ROW
            BEGIN
              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.valor_total),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = NEW.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = NEW.pedido_id;
            END
        ");

        // 2. tr_items_pedido_au_recalc_pedido_total
        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_au_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_au_recalc_pedido_total
            AFTER UPDATE ON items_pedido
            FOR EACH ROW
            BEGIN
              IF OLD.pedido_id <> NEW.pedido_id THEN
                UPDATE pedidos p
                SET p.valor_total =
                    (SELECT COALESCE(SUM(ip.valor_total),0)
                       FROM items_pedido ip
                      WHERE ip.pedido_id = OLD.pedido_id
                        AND ip.status_item = 'ativo')
                    + COALESCE(p.valor_frete,0)
                    - COALESCE(p.valor_desconto,0)
                WHERE p.id = OLD.pedido_id;
              END IF;

              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.valor_total),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = NEW.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = NEW.pedido_id;
            END
        ");

        // 3. tr_items_pedido_ad_recalc_pedido_total
        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_ad_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_ad_recalc_pedido_total
            AFTER DELETE ON items_pedido
            FOR EACH ROW
            BEGIN
              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.valor_total),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = OLD.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = OLD.pedido_id;
            END
        ");

        // 4. tr_pedidos_bu_recalc_total_on_frete_desconto
        DB::unprepared("DROP TRIGGER IF EXISTS tr_pedidos_bu_recalc_total_on_frete_desconto");
        DB::unprepared("
            CREATE TRIGGER tr_pedidos_bu_recalc_total_on_frete_desconto
            BEFORE UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
              IF (COALESCE(OLD.valor_frete,0) <> COALESCE(NEW.valor_frete,0))
                 OR (COALESCE(OLD.valor_desconto,0) <> COALESCE(NEW.valor_desconto,0)) THEN

                SET NEW.valor_total =
                  (SELECT COALESCE(SUM(ip.valor_total),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = OLD.id
                      AND ip.status_item = 'ativo')
                  + COALESCE(NEW.valor_frete,0)
                  - COALESCE(NEW.valor_desconto,0);
              END IF;
            END
        ");

        // 5. Recalcular valores totais dos pedidos existentes para sincronizar com os triggers novos
        $pedidos = DB::table('pedidos')->get();
        foreach ($pedidos as $p) {
            $sum = DB::table('items_pedido')
                ->where('pedido_id', $p->id)
                ->where('status_item', 'ativo')
                ->sum('valor_total') ?? 0;

            $newTotal = $sum + $p->valor_frete - $p->valor_desconto;
            
            if ($newTotal != $p->valor_total) {
                DB::table('pedidos')->where('id', $p->id)->update(['valor_total' => $newTotal]);
                
                // Trigar o observer para atualizar o financeiro/ledger do cliente
                $pedidoModel = \App\Models\Pedido::find($p->id);
                if ($pedidoModel) {
                    $pedidoModel->touch();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter triggers para usar preco_unitario (comportamento anterior)
        
        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_ai_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_ai_recalc_pedido_total
            AFTER INSERT ON items_pedido
            FOR EACH ROW
            BEGIN
              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.preco_unitario),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = NEW.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = NEW.pedido_id;
            END
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_au_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_au_recalc_pedido_total
            AFTER UPDATE ON items_pedido
            FOR EACH ROW
            BEGIN
              IF OLD.pedido_id <> NEW.pedido_id THEN
                UPDATE pedidos p
                SET p.valor_total =
                    (SELECT COALESCE(SUM(ip.preco_unitario),0)
                       FROM items_pedido ip
                      WHERE ip.pedido_id = OLD.pedido_id
                        AND ip.status_item = 'ativo')
                    + COALESCE(p.valor_frete,0)
                    - COALESCE(p.valor_desconto,0)
                WHERE p.id = OLD.pedido_id;
              END IF;

              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.preco_unitario),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = NEW.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = NEW.pedido_id;
            END
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS tr_items_pedido_ad_recalc_pedido_total");
        DB::unprepared("
            CREATE TRIGGER tr_items_pedido_ad_recalc_pedido_total
            AFTER DELETE ON items_pedido
            FOR EACH ROW
            BEGIN
              UPDATE pedidos p
              SET p.valor_total =
                  (SELECT COALESCE(SUM(ip.preco_unitario),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = OLD.pedido_id
                      AND ip.status_item = 'ativo')
                  + COALESCE(p.valor_frete,0)
                  - COALESCE(p.valor_desconto,0)
              WHERE p.id = OLD.pedido_id;
            END
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS tr_pedidos_bu_recalc_total_on_frete_desconto");
        DB::unprepared("
            CREATE TRIGGER tr_pedidos_bu_recalc_total_on_frete_desconto
            BEFORE UPDATE ON pedidos
            FOR EACH ROW
            BEGIN
              IF (COALESCE(OLD.valor_frete,0) <> COALESCE(NEW.valor_frete,0))
                 OR (COALESCE(OLD.valor_desconto,0) <> COALESCE(NEW.valor_desconto,0)) THEN

                SET NEW.valor_total =
                  (SELECT COALESCE(SUM(ip.preco_unitario),0)
                     FROM items_pedido ip
                    WHERE ip.pedido_id = OLD.id
                      AND ip.status_item = 'ativo')
                  + COALESCE(NEW.valor_frete,0)
                  - COALESCE(NEW.valor_desconto,0);
              END IF;
            END
        ");
    }
};
