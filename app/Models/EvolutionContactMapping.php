<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvolutionContactMapping extends Model
{
    use HasFactory;

    protected $table = 'evolution_contact_mappings';

    protected $fillable = [
        'lid',
        'phone',
        'remote_jid',
        'name',
    ];
}
