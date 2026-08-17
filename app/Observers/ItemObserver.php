<?php

namespace App\Observers;

use App\Models\Item;
use App\Jobs\MatchProductWithWishesJob;

class ItemObserver
{
    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item): void
    {
        // Ao criar um novo item (produto), dispara o Job para verificar a lista de desejos
        // de forma assíncrona para não travar a criação.
        MatchProductWithWishesJob::dispatch($item);
    }
}
