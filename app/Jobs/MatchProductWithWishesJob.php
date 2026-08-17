<?php

namespace App\Jobs;

use App\Models\Item;
use App\Models\ClientWish;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MatchProductWithWishesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Item $item;

    /**
     * Create a new job instance.
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Iniciando MatchProductWithWishesJob para Item ID: {$this->item->id}");

        // Extrai categoria e tamanho do item (dependendo de como está na sua tabela Item)
        // Como não conheço 100% da sua tabela Item, assumo propriedades comuns:
        $itemCategory = $this->item->categoria_id ?? $this->item->categoria ?? null; // Exemplo de ajuste necessário dependendo da model
        $itemSize = $this->item->tamanho ?? null;
        $itemPrice = $this->item->preco_venda ?? 0;

        // Se faltar informação vital, não temos como fazer match exato.
        if (!$itemSize) {
            Log::info("Item ID {$this->item->id} não tem tamanho definido. Abortando match.");
            return;
        }

        // Buscar desejos ativos que combinem com os critérios
        // Regra base: mesmo tamanho, preço <= max_price (se max_price definido)
        $query = ClientWish::where('status', 'active')
            ->where('size', $itemSize);

        if ($itemPrice > 0) {
            $query->where(function ($q) use ($itemPrice) {
                $q->whereNull('max_price')
                  ->orWhere('max_price', '>=', $itemPrice);
            });
        }

        // Se tivéssemos o nome/categoria normalizado no item, faríamos match aqui
        // $query->where('category', $itemCategory);

        $matches = $query->get();

        if ($matches->isEmpty()) {
            Log::info("Nenhum match encontrado para Item ID: {$this->item->id}");
            return;
        }

        foreach ($matches as $wish) {
            // Em um sistema real avançado, faríamos uma verificação adicional de NLP (Keywords)
            // Aqui faremos o básico conforme pedido.

            // Atualiza status do desejo
            $wish->status = 'matched';
            $wish->save();

            Log::info("Match! Wish ID {$wish->id} da usuária {$wish->user_id} bateu com Item ID {$this->item->id}.");

            // TODO: Disparar notificação para a usuária (Ex: E-mail, WhatsApp ou Notification Database)
            // event(new WishMatched($wish, $this->item));
            // ou Notification::send($wish->user, new ItemMatchedNotification($wish, $this->item));
        }
    }
}
