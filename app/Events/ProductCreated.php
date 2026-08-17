<?php

namespace App\Events;

use App\Models\Item;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Item $item;

    /**
     * Create a new event instance.
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
    }
}
