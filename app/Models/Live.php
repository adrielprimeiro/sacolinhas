<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Live extends Model
{
    use HasFactory;

    protected $table = 'lives';
    
    protected $fillable = [
        'data',
        'tipo_live',
        'plataformas',
        'ativo',
        'encerrada_em'
    ];

    protected $casts = [
        'data' => 'date',
        'ativo' => 'boolean',
        'encerrada_em' => 'datetime',
    ];

    // ✅ TODOS SEUS MÉTODOS ORIGINAIS (mantidos 100%)
    public function getTipoLiveFormatadoAttribute()
    {
        $tipos = [
            'loja-aberta' => 'Loja Aberta',
            'leilao' => 'Leilão',
            'precinho' => 'Precinho'
        ];
        return $tipos[$this->tipo_live] ?? $this->tipo_live;
    }

    public function getPlataformasArrayAttribute()
    {
        if (empty($this->plataformas)) return [];
        return is_string($this->plataformas) ? explode(',', $this->plataformas) : [];
    }

    public function setPlataformasAttribute($value)
    {
        $this->attributes['plataformas'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function scopeToday($query)
    {
        return $query->whereDate('data', Carbon::today());
    }

    // ✅ NOVOS MÉTODOS: Participantes + Track (sem relações!)
    
    /**
     * Participantes: Users que mandaram msg inbound na live (±24h da data)
     */
    public function getParticipantsAttribute()
    {
        return \App\Models\User::whereHas('whatsappMessages', function ($query) {
            $query->where('direction', 'inbound')
                  ->where('live_id', $this->id)  // Se já setado
                  ->orWhereBetween('created_at', [
                      $this->data->subHours(24),
                      $this->data->addHours(24)
                  ]);
        })->with('whatsapp')->get();
    }

    /**
     * Mensagens finais enviadas (outbound com live_id)
     */
    public function getFinalMessagesAttribute()
    {
        return \DB::table('whatsapp_messages')
            ->where('live_id', $this->id)
            ->where('direction', 'outbound')
            ->select('user_id', 'status', 'message_sid', 'body', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($msg) {
                $msg->user = \App\Models\User::select('id', 'name', 'whatsapp')->find($msg->user_id);
                return $msg;
            });
    }

    /**
     * Status resumo entregas
     */
    public function getDeliveryStatsAttribute()
    {
        $stats = \DB::table('whatsapp_messages')
            ->where('live_id', $this->id)
            ->where('direction', 'outbound')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($stats);
        $delivered = ($stats['delivered'] ?? 0) + ($stats['read'] ?? 0);
        
        return [
            'delivered' => $stats['delivered'] ?? 0,
            'read' => $stats['read'] ?? 0,
            'failed' => $stats['failed'] ?? $stats['undelivered'] ?? 0,
            'total' => $total,
            'success_rate' => $total ? round($delivered / $total * 100, 1) : 0
        ];
    }
}