<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegistrarMensalidadeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Domains\Clube\Jobs\RecalcularIndicadoresClienteJob;
use Carbon\Carbon;

class ClubeMensalidadesController extends Controller
{
    /**
     * Exibe o formulário de registro de mensalidade.
     */
    public function create()
    {
        // Alterado para $users para casar com o autocomplete do Blade
        // Adicionado 'email' para que o display do "old user" funcione corretamente
        $users = User::where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.clube.mensalidades.registrar', compact('users'));
    }

    /**
     * Processa o registro da mensalidade e ativa a assinatura.
     */
    public function store(RegistrarMensalidadeRequest $request)
    {
        $data = $request->validated();

        // Extrai Ano e Mês da competência (formato YYYY-MM)
        [$ano, $mes] = array_map('intval', explode('-', $data['competencia']));

        DB::transaction(function () use ($data, $ano, $mes) {
            
            // 1) Garante assinatura ativa para o usuário
            $assinatura = DB::table('clube_assinaturas')
                ->where('user_id', $data['user_id'])
                ->first(['id', 'status']);

            if (!$assinatura) {
                // Se não existe, cria uma nova ativa
                $assinaturaId = DB::table('clube_assinaturas')->insertGetId([
                    'user_id'    => $data['user_id'],
                    'status'     => 'ativa',
                    'inicio_em'  => now()->toDateString(),
                    'fim_em'     => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $assinaturaId = $assinatura->id;

                // Se existe mas não está ativa, ativa agora
                if ($assinatura->status !== 'ativa') {
                    DB::table('clube_assinaturas')
                        ->where('id', $assinaturaId)
                        ->update([
                            'status'     => 'ativa',
                            'updated_at' => now(),
                        ]);
                }
            }

            // 2) Grava ou Atualiza a mensalidade como PAGA
            // Buscamos se já existe registro para esse cliente nesta competência
            $mensalidade = DB::table('clube_mensalidades')
                ->where('user_id', $data['user_id'])
                ->where('competencia_ano', $ano)
                ->where('competencia_mes', $mes)
                ->first(['id']);

            $payloadMensalidade = [
                'assinatura_id'    => $assinaturaId,
                'status_pagamento' => 'pago',
                'pago_em'          => $data['data_pagamento'], // O MySQL aceita string de data em datetime
                'valor'            => $data['valor'],
            ];

            if ($mensalidade) {
                DB::table('clube_mensalidades')
                    ->where('id', $mensalidade->id)
                    ->update($payloadMensalidade);
            } else {
                // Se for novo, adicionamos os campos de identificação e o created_at
                $payloadMensalidade['user_id']         = $data['user_id'];
                $payloadMensalidade['competencia_ano'] = $ano;
                $payloadMensalidade['competencia_mes'] = $mes;
                $payloadMensalidade['created_at']      = now();

                DB::table('clube_mensalidades')->insert($payloadMensalidade);
            }
        });

        // 3) Recalcular indicadores (Job assíncrono)
        // Usamos afterCommit() para garantir que o Job só rode após o banco confirmar a transação acima
        RecalcularIndicadoresClienteJob::dispatch((int) $data['user_id'])->afterCommit();

        return redirect()
            ->route('admin.clube.mensalidades.create')
            ->with('status', 'Mensalidade registrada com sucesso! Os indicadores do cliente estão sendo recalculados.');
    }
}