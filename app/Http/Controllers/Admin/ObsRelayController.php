<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ObsRelayCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ObsRelayController
 *
 * Permite que o browser no PC B envie comandos ao OBS que está no PC A.
 * O agente (script Node.js) no PC A faz polling aqui e executa os comandos localmente.
 *
 * Fluxo:
 *   PC B (browser) → POST /admin/obs-relay/command    → salva na fila (status: pending)
 *   PC A (agente)  → GET  /admin/obs-relay/pending    → busca pendentes (status: processing)
 *   PC A (agente)  → POST /admin/obs-relay/{id}/done  → marca como done + salva resultado
 *   PC B (browser) → GET  /admin/obs-relay/{id}/status → consulta resultado (outputPath)
 */
class ObsRelayController extends Controller
{
    /**
     * PC B → enfileira um comando OBS.
     */
    public function queueCommand(Request $request)
    {
        $request->validate([
            'command_type' => 'required|string|in:StartRecord,StopRecord,GetRecordStatus,GetVersion',
            'payload'      => 'nullable|array',
        ]);

        $cmd = ObsRelayCommand::create([
            'command_type' => $request->command_type,
            'payload'      => $request->payload ?? [],
            'status'       => 'pending',
        ]);

        Log::info('[OBS Relay] Comando enfileirado', [
            'id'      => $cmd->id,
            'command' => $cmd->command_type,
        ]);

        return response()->json([
            'ok'  => true,
            'id'  => $cmd->id,
            'cmd' => $cmd->command_type,
        ]);
    }

    /**
     * PC A (agente) → busca comandos pendentes e os marca como "processing".
     * Também limpa comandos antigos (>10 min) para não acumular lixo.
     */
    public function pollPending(Request $request)
    {
        // Limpar comandos velhos que não foram executados (>10 minutos)
        ObsRelayCommand::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(10))
            ->update(['status' => 'error', 'result' => 'timeout: agente não disponível']);

        // Busca pendentes (em ordem FIFO)
        $commands = ObsRelayCommand::where('status', 'pending')
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($commands->isEmpty()) {
            return response()->json(['commands' => []]);
        }

        $agentId = $request->header('X-Agent-ID', 'unknown');

        // Marca todos como "processing" para evitar dupla execução
        ObsRelayCommand::whereIn('id', $commands->pluck('id'))
            ->update(['status' => 'processing', 'agent_id' => $agentId]);

        return response()->json([
            'commands' => $commands->map(fn($c) => [
                'id'           => $c->id,
                'command_type' => $c->command_type,
                'payload'      => $c->payload,
            ])->values(),
        ]);
    }

    /**
     * PC A (agente) → reporta que o comando foi executado e envia o resultado.
     */
    public function markDone(Request $request, $id)
    {
        $cmd = ObsRelayCommand::findOrFail($id);

        $cmd->update([
            'status'      => $request->input('error') ? 'error' : 'done',
            'result'      => json_encode($request->input('result', [])),
            'executed_at' => now(),
        ]);

        Log::info('[OBS Relay] Comando executado', [
            'id'     => $cmd->id,
            'status' => $cmd->status,
            'result' => $cmd->result,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * PC B (browser) → consulta o status/resultado de um comando específico.
     */
    public function getCommandStatus($id)
    {
        $cmd = ObsRelayCommand::findOrFail($id);

        $result = null;
        if ($cmd->result) {
            $result = json_decode($cmd->result, true);
        }

        return response()->json([
            'id'      => $cmd->id,
            'status'  => $cmd->status,
            'result'  => $result,
        ]);
    }

    /**
     * Health check — o agente usa para verificar conectividade com o servidor.
     */
    public function health()
    {
        return response()->json([
            'ok'   => true,
            'time' => now()->toISOString(),
        ]);
    }
}
