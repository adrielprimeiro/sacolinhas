<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Pedido;
use App\Models\PedidoRastreamento;

class MelhorEnvioWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Melhor Envio Webhook recebido:', $request->all());

        // A estrutura exata do payload pode variar, mas geralmente o Melhor Envio envia
        // um objeto com o "tracking" ou "id" do pacote, e os dados do evento de rastreio.
        $payload = $request->all();

        // O payload pode ser um array de eventos ou um evento único
        // Verifica se é um array de múltiplos eventos
        if (isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $event) {
                $this->processEvent($event);
            }
        } else {
            $this->processEvent($payload);
        }

        return response()->json(['success' => true]);
    }

    private function processEvent($event)
    {
        // Se for um evento de rastreio (tracking), normalmente teremos "tracking" e "status_id" ou "status"
        $tracking = $event['tracking'] ?? null;
        $statusStr = strtolower($event['status'] ?? '');
        
        if (!$tracking) {
            return;
        }

        $pedido = Pedido::where('codigo_rastreamento', $tracking)->first();

        if (!$pedido) {
            Log::warning("Melhor Envio Webhook: Pedido com rastreio {$tracking} não encontrado.");
            return;
        }

        // Mapear os status do Melhor Envio (posted, in transit, delivered, canceled, etc.)
        if (in_array($statusStr, ['posted', 'in transit', 'out for delivery'])) {
            if (empty($pedido->data_envio)) {
                $pedido->data_envio = now();
            }
        } elseif ($statusStr === 'delivered') {
            $pedido->data_entrega_realizada = now();
            $pedido->status_pedido = 'concluido'; // Conforme aprovado
        }

        $pedido->save();

        // Mapeamento e Inserção do Histórico
        $traducaoStatus = [
            'pending' => 'Pendente',
            'released' => 'Liberado para envio',
            'posted' => 'Postado',
            'in transit' => 'Em trânsito',
            'out for delivery' => 'Saiu para entrega',
            'delivered' => 'Entregue',
            'undelivered' => 'Não entregue',
            'canceled' => 'Cancelado'
        ];

        $descricaoMap = [
            'pending' => 'Aguardando pagamento ou liberação da etiqueta.',
            'released' => 'A etiqueta foi liberada e está pronta para postagem.',
            'posted' => 'O pacote foi entregue na agência ou transportadora.',
            'in transit' => 'Seu pacote está viajando para a próxima unidade de distribuição.',
            'out for delivery' => 'O entregador já saiu com seu pacote. Prepare-se para receber!',
            'delivered' => 'O pacote foi entregue no destino.',
            'undelivered' => 'Houve um problema na entrega. O pacote pode retornar ao remetente.',
            'canceled' => 'O envio foi cancelado.'
        ];

        $statusTraduzido = $traducaoStatus[$statusStr] ?? ucfirst($statusStr);
        $descricao = $descricaoMap[$statusStr] ?? 'Atualização de rastreamento recebida.';
        
        $dataHoraEvento = isset($event['date']) ? \Carbon\Carbon::parse($event['date']) : now();

        // Evitar duplicidade do exato mesmo status no mesmo pedido
        $exists = PedidoRastreamento::where('pedido_id', $pedido->id)
            ->where('status', $statusTraduzido)
            ->exists();

        if (!$exists) {
            PedidoRastreamento::create([
                'pedido_id' => $pedido->id,
                'status' => $statusTraduzido,
                'descricao' => $descricao,
                'data_hora' => $dataHoraEvento
            ]);
        }

        Log::info("Melhor Envio Webhook: Pedido {$pedido->id} atualizado (Rastreio: {$tracking}, Status: {$statusStr})");
    }
}
