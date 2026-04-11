<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Live; 
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\SendWhatsAppMessage;
use Carbon\Carbon;


class LiveController extends Controller
{
    /**
     * Buscar live ativa
     */
    public function index()
    {
        try {
            $live = DB::table('lives')
                      ->where('ativo', 1)
                      ->orderBy('created_at', 'desc')
                      ->first();
            
            if (!$live) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhuma live ativa no momento',
                    'live' => null,
                    'live_id' => null,
                    'has_active_live' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'live_id' => $live->id,
                'live' => $live,
                'has_active_live' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar live ativa: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar live ativa: ' . $e->getMessage(),
                'has_active_live' => false
            ], 500);
        }
    }

    /**
     * Criar nova live
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'plataformas.*' => 'string|in:instagram,tiktok,youtube,facebook'
            ]);

            // Verificar se já existe uma live ativa
            $liveAtiva = DB::table('lives')->where('ativo', 1)->first();
            
            if ($liveAtiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma live ativa. Encerre-a antes de criar uma nova.'
                ], 400);
            }

            // Criar nova live
            $liveId = DB::table('lives')->insertGetId([
                'data' => now()->format('Y-m-d'),
                'tipo_live' => $request->tipo_live,
                'plataformas' => implode(',', $request->plataformas),
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $live = DB::table('lives')->where('id', $liveId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Live criada com sucesso!',
                'live' => $live
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao criar live: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar live: ' . $e->getMessage()
            ], 500);
        }
    }

	/**
	 * Encerrar live com validação de telefones e logs aprimorados
	 */
    public function destroy($id)
    {
        $liveId = (int) $id;
		$enviarWhatsapp = (int) request()->query('enviar_whatsapp', 1) === 1;

        // 1) Valida live e pega clientes
        DB::beginTransaction();

        try {
            $live = DB::table('lives')
                ->where('id', $liveId)
                ->lockForUpdate()
                ->first();

            if (!$live || (int)$live->ativo !== 1) {
                DB::rollBack();
                return response()->json(['error' => 'Live não encontrada ou já encerrada'], 404);
            }

            $clientesIds = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->distinct()
                ->pluck('user_id');

            if ($clientesIds->isEmpty()) {
                DB::rollBack();
                return response()->json(['error' => 'Nenhuma sacolinha encontrada'], 404);
            }

            DB::commit();
			
			// ✅ Se escolheu encerrar SEM envio: não gera PDF e não enfileira jobs
			if (!$enviarWhatsapp) {
				try {
					DB::table('lives')->where('id', $liveId)->update([
						'ativo' => 0,
						'encerrada_em' => now(),
						'updated_at' => now(),
					]);

					Log::info('Live encerrada SEM envio WhatsApp (sem gerar PDF)', [
						'live_id' => $liveId,
						'clientes' => $clientesIds->count(),
					]);

					return response()->json([
						'success' => true,
						'message' => 'Live encerrada sem envio de mensagens.',
						'pdfs_ok' => 0,
						'jobs_enfileirados' => 0,
					]);
				} catch (\Throwable $e) {
					Log::error('Erro ao encerrar live (sem envio): ' . $e->getMessage(), ['live_id' => $liveId]);
					return response()->json(['error' => $e->getMessage()], 500);
				}
			}			
			
			
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro Destroy (pré): ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // 2) Fora da transação: gera PDFs e grava/atualiza live_pdfs
        $clientesComPdf = [];
        foreach ($clientesIds as $clienteId) {
            $clienteId = (int) $clienteId;

            try {
                $pdfInfo = $this->gerarPdfSacolinhaLiveSalvar($clienteId, $liveId); // retorna ['path','url']

                // updateOrInsert na sua tabela real
                DB::table('live_pdfs')->updateOrInsert(
                    ['live_id' => $liveId, 'user_id' => $clienteId],
                    [
                        'pdf_path' => $pdfInfo['path'],
                        'pdf_url'  => $pdfInfo['url'],
                        'status'   => 'ready',
                        'sent_at'  => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $clientesComPdf[] = $clienteId;
            } catch (\Throwable $e) {
                Log::error('Erro PDF', [
                    'live_id' => $liveId,
                    'user_id' => $clienteId,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        // 3) Enfileira WhatsApp SOMENTE para quem tem PDF pronto
        $jobsEnfileirados = 0;
        $delay = 0;

        foreach ($clientesComPdf as $clienteId) {
            $user = DB::table('users')->where('id', $clienteId)->first();
            if (!$user) {
                Log::warning('Usuário não encontrado para envio WhatsApp', [
                    'live_id' => $liveId,
                    'user_id' => $clienteId,
                ]);
                continue;
            }

            $telefone = $user->whatsapp ?? $user->telefone_principal ?? $user->telefone_2;
			if (!$telefone) {
				Log::warning('Sem telefone para envio WhatsApp', [
                    'live_id' => $liveId,
                    'user_id' => $clienteId,
                ]);
				DB::table('whatsapp_messages')->insert([
					'user_id' => $clienteId,
					'live_id' => $liveId,
					'direction' => 'outbound',
					'status' => 'failed',
					'message_type' => 'first',
					'failed_reason' => 'Sem telefone cadastrado',
					'retry_count' => 0,
					'status_updated_at' => now(),
					'created_at' => now(),
					'updated_at' => now(),
				]);
				continue;
			}			
			
			
			
			

            // ✅ CORREÇÃO: Garantir que o Job seja chamado com messageType = 'first'
            SendWhatsAppMessage::dispatch($telefone, $liveId, $clienteId, 'first')
                ->onQueue('whatsapp')
                ->delay(now()->addSeconds($delay));

            Log::info('Job WhatsApp enfileirado', [
                'live_id' => $liveId,
                'user_id' => $clienteId,
                'telefone' => $telefone,
                'delay' => $delay,
            ]);

            $jobsEnfileirados++;
            $delay++;
        }

        // 4) Encerra a live (agora de fato)
        try {
            DB::table('lives')->where('id', $liveId)->update([
                'ativo' => 0,
                'encerrada_em' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Live encerrada com sucesso', [
                'live_id' => $liveId,
                'pdfs_ok' => count($clientesComPdf),
                'jobs_enfileirados' => $jobsEnfileirados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao encerrar live (update): ' . $e->getMessage(), ['live_id' => $liveId]);
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Live encerrada. PDFs gerados e mensagens enfileiradas.',
            'pdfs_ok' => count($clientesComPdf),
            'jobs_enfileirados' => $jobsEnfileirados,
        ]);
    }


    // ✅ NOVO MÉTODO PARA ENVIAR MENSAGENS VIA JOBS
   /* private function enviarMensagensWhatsApp($clientesIds, $liveId)
    {
        $jobsEnfileirados = 0;
        $delaySegundos = 0;
        
        foreach ($clientesIds as $userId) {
            // Buscar telefone do usuário
            $user = DB::table('users')->where('id', $userId)->first();
            
            if (!$user) {
                continue;
            }
            
            // Priorizar: whatsapp > telefone_principal > phone
            $telefone = $user->whatsapp ?: $user->telefone_principal ?: $user->phone;
            
            if (empty($telefone)) {
                Log::warning('Telefone vazio para usuário', [
                    'user_id' => $userId,
                    'live_id' => $liveId
                ]);
                continue;
            }
            
            // Formatar telefone
            $telefoneFormatado = $this->formatarTelefoneE164($telefone);
            
            if (!$telefoneFormatado) {
                Log::warning('Telefone inválido para usuário', [
                    'user_id' => $userId,
                    'live_id' => $liveId,
                    'telefone' => $telefone
                ]);
                continue;
            }
            
            // Enfileirar job
            SendWhatsAppMessage::dispatch(
                $telefoneFormatado,
                $liveId,
                $userId
            )->onQueue('whatsapp')->delay(now()->addSeconds($delaySegundos));
            
            $jobsEnfileirados++;
            $delaySegundos++; // Rate limit: 1 envio por segundo
        }
        
        Log::info('Jobs de WhatsApp enfileirados', [
            'live_id' => $liveId,
            'jobs_enfileirados' => $jobsEnfileirados
        ]);
    }

    private function formatarTelefoneE164($telefone)
    {
        // Remover todos os caracteres não numéricos
        $telefone = preg_replace('/\D/', '', $telefone);
        
        // Verificar se é um telefone brasileiro válido
        if (strlen($telefone) === 11 && substr($telefone, 0, 2) === '55') {
            return '+' . $telefone;
        }
        
        if (strlen($telefone) === 11) {
            return '+55' . $telefone;
        }
        
        if (strlen($telefone) === 10) {
            $telefone = substr($telefone, 0, 2) . '9' . substr($telefone, 2);
            return '+55' . $telefone;
        }
        
        return false;
    }
*/

	public function gerarPdfSacolinhaLiveSalvar(int $clienteId, int $liveId): array
	{
		$cliente = User::findOrFail($clienteId);

		// ✅ APENAS sacolinhas da live (igual ao imprimirSacolinha, mas com filtro da live)
		$itensSacolinha = DB::table('sacolinhas')
			->join('items', 'sacolinhas.item_id', '=', 'items.id')
			->where('sacolinhas.user_id', $clienteId)
			->where('sacolinhas.live_id', $liveId)
			->select(
				'items.codigo',
				'items.nome_do_produto',
				'sacolinhas.price',
				'items.marca',
				'items.estado',
				'items.cor',
				'items.tamanho',
				'sacolinhas.add_at',
				'sacolinhas.obs'
			)
			->get();

		if ($itensSacolinha->count() === 0) {
			throw new \RuntimeException('Nenhum item encontrado na sacolinha desta live');
		}

		$valorTotal = (float) $itensSacolinha->sum('price');
		$totalItens = (int) $itensSacolinha->count();

		// Logo (igual)
		$logoPath = public_path('images/LogoColorida sem fundo.png');
		$logoDataUri = null;

		if (file_exists($logoPath)) {
			$logoMime = mime_content_type($logoPath) ?: 'image/png';
			$logoBase64 = base64_encode(file_get_contents($logoPath));
			$logoDataUri = "data:{$logoMime};base64,{$logoBase64}";
		}

		// ✅ HTML IGUAL ao imprimirSacolinha (com live_id no título opcional)
		$html = '
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>Sacolinha - ' . htmlspecialchars($cliente->name) . '</title>
			<style>
			  body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
			  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
			  th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
			  th { background: #f0f0f0; font-weight: bold; }
			  .header { margin-bottom: 20px; }
			  .header-grid { width: 100%; border-collapse: collapse; }
			  .header-grid, .header-grid td, .header-grid th { border: none !important; }
			  .header-grid td { padding: 0 !important; }
			  .header-grid { width: 100%; }
			  .logo-cell { width: 120px; vertical-align: top; }
			  .logo { height: 60px; }
			  .title-cell { text-align: center; vertical-align: top; }
			  .title-cell h1 { margin: 0 0 6px 0; }
			  .title-cell p { margin: 3px 0; }
			  .total { background: #e8f4fd; font-weight: bold; }
			  .preco { text-align: right; }
			</style>
		</head>
		<body>
			<div class="header">
			  <table class="header-grid">
				<tr>
				  <td class="logo-cell">
					' . ($logoDataUri ? '<img class="logo" src="' . $logoDataUri . '" />' : '') . '
				  </td>
				  <td class="title-cell">
					<h1>Sacolinha Mania</h1>
					<p><strong>Cliente:</strong> ' . htmlspecialchars($cliente->name) . '</p>
					<p><strong>Live:</strong> #' . (int)$liveId . '</p>
					<p><strong>Data:</strong> ' . date('d/m/Y H:i:s') . '</p>
					<p><strong>Total de Itens:</strong> ' . $totalItens . '</p>
					<p><strong>Valor Total:</strong> R$ ' . number_format($valorTotal, 2, ',', '.') . '</p>
				  </td>
				  <td style="width:120px;"></td>
				</tr>
			  </table>
			</div>

			<table>
				<thead>
					<tr>
						<th>Código</th>
						<th>Produto</th>
						<th>Detalhes</th>
						<th class="preco">Preço</th>
						<th>Dia da Live</th>
					</tr>
				</thead>
				<tbody>';

		foreach ($itensSacolinha as $item) {
			$detalhes = [];
			if (!empty($item->marca))  $detalhes[] = $item->marca;
			if (!empty($item->estado)) $detalhes[] = $item->estado;
			if (!empty($item->cor))    $detalhes[] = $item->cor;
			if (!empty($item->tamanho)) $detalhes[] = 'Tam: ' . $item->tamanho;

			// ✅ inclui OBS (se existir)
			if (!empty($item->obs)) {
				$detalhes[] = 'Obs: ' . $item->obs;
			}

			$html .= '<tr>
				<td>' . htmlspecialchars($item->codigo ?? 'N/A') . '</td>
				<td><strong>' . htmlspecialchars($item->nome_do_produto ?? '') . '</strong></td>
				<td>' . htmlspecialchars(implode(' • ', $detalhes)) . '</td>
				<td class="preco">R$ ' . number_format((float)$item->price, 2, ',', '.') . '</td>
				<td>' . \Carbon\Carbon::parse($item->add_at)->format('d/m/Y') . '</td>
			</tr>';
		}

		$html .= '
				</tbody>
				<tfoot>
					<tr class="total">
						<td colspan="3"><strong>TOTAL GERAL:</strong></td>
						<td class="preco"><strong>R$ ' . number_format($valorTotal, 2, ',', '.') . '</strong></td>
						<td></td>
					</tr>
				</tfoot>
			</table>
		</body>
		</html>';

		$pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

		$relativePath = "pedidos/live_{$liveId}/pedido_cliente_{$clienteId}.pdf";
		Storage::disk('public')->put($relativePath, $pdf->output());

		$url = asset("storage/{$relativePath}");

		return [
			'path' => $relativePath,
			'url' => $url,
		];
	}	
	
	

    /**
     * Obter todas as lives
     */
    public function getAllLives()
    {
        $lives = Live::orderBy('created_at', 'desc')->get();

        $formattedLives = $lives->map(function ($live) {
            $live->status = $live->is_active ? 'ativa' : 'encerrada';
            return $live;
        });

        return response()->json(['success' => true, 'data' => $formattedLives]);
    }

    /**
     * Exibir visão de sacolas da live
     */
    public function showLiveBagsOverview()
    {
        return view('admin.sacolinhas.index');
    }

    /**
     * Buscar usuários
     */
	public function search(Request $request)
	{
		try {
			$query = $request->get('q', '');
			$role = $request->get('role', 'client');
			
			if (empty($query)) {
				return response()->json(['success' => true, 'data' => []]);
			}
			
			// ✅ BUSCA EM AMBOS OS CAMPOS (antigos e novos)
			$users = User::where('role', $role)
				->where(function($q) use ($query) {
					$searchTerm = "%{$query}%";
					
					// Dados pessoais
					$q->where('name', 'LIKE', $searchTerm)
					  ->orWhere('email', 'LIKE', $searchTerm)
					  ->orWhere('apelido', 'LIKE', $searchTerm)
					  
					  // ✅ CAMPOS NOVOS (prioridade)
					  ->orWhere('instagram', 'LIKE', $searchTerm)
					  ->orWhere('tiktok', 'LIKE', $searchTerm)
					  ->orWhere('whatsapp', 'LIKE', $searchTerm)
					  
					  // ✅ CAMPOS ANTIGOS (compatibilidade)
					  ->orWhere('remember_token', 'LIKE', $searchTerm)
					  ->orWhere('nome_cliente', 'LIKE', $searchTerm)
					  ->orWhere('phone', 'LIKE', $searchTerm);
					
					// Buscar por ID se for número
					if (is_numeric($query)) {
						$q->orWhere('id', $query);
					}
				})
				->limit(10)
				->get();

			// ✅ CONSTRUIR ARRAY COM TODOS OS CAMPOS
			$usersArray = [];
			foreach ($users as $user) {
				$usersArray[] = [
					'id' => $user->id,
					'name' => $user->name,
					'email' => $user->email,
					'apelido' => $user->apelido,
					'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&size=40",
					
					// ✅ CAMPOS NOVOS
					'instagram' => $user->instagram,
					'tiktok' => $user->tiktok,
					'whatsapp' => $user->whatsapp,
					
					// ✅ CAMPOS ANTIGOS (para compatibilidade)
					'remember_token' => $user->remember_token,
					'nome_cliente' => $user->nome_cliente,
					'phone' => $user->phone
				];
			}

			return response()->json([
				'success' => true,
				'data' => $usersArray,
				'search_term' => $query
			]);

		} catch (\Exception $e) {
			Log::error("Erro na busca: " . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro na busca'
			], 500);
		}
	}


    /**
     * Criar novo cliente com dados básicos
     */
    public function quickCreate(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $name = $request->input('name');
            
            // Gerar email temporário único
            $emailBase = strtolower(str_replace(' ', '.', $name));
            $emailBase = preg_replace('/[^a-z0-9.]/', '', $emailBase);
            $timestamp = time();
            $email = "{$emailBase}.{$timestamp}@temp.cliente.com";
            
            // Criar novo cliente com dados padronizados
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => null,
                'role' => 'client',
                'password' => bcrypt('temp123'),
                'email_verified_at' => null,
            ]);

            Log::info("Novo cliente criado via busca rápida: {$user->name} (ID: {$user->id})");

            return response()->json([
                'success' => true,
                'message' => 'Cliente criado com sucesso!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&size=40",
                    'created_now' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao criar cliente rápido: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar cliente: ' . $e->getMessage()
            ], 500);
        }
    }
			
			
	public function sendFirstToClient($liveId, $userId)
	{
		$liveId = (int) $liveId;
		$userId = (int) $userId;

		// 1) valida live (SEM exigir ativo=1; pode ser encerrada)
		$live = DB::table('lives')->where('id', $liveId)->first();
		if (!$live) {
			return response()->json(['success' => false, 'message' => 'Live não encontrada'], 404);
		}

		// 2) valida se esse cliente tem sacolinha nessa live (recomendado)
		$temSacola = DB::table('sacolinhas')
			->where('live_id', $liveId)
			->where('user_id', $userId)
			->exists();

		if (!$temSacola) {
			return response()->json([
				'success' => false,
				'message' => 'Esse cliente não possui sacolinha nesta live.'
			], 404);
		}

		// 3) garante PDF ready (igual Encerrar Live)
		try {
			$pdfRow = DB::table('live_pdfs')
				->where('live_id', $liveId)
				->where('user_id', $userId)
				->first();

			if (!$pdfRow || $pdfRow->status !== 'ready') {
				$pdfInfo = $this->gerarPdfSacolinhaLiveSalvar($userId, $liveId); // ['path','url']

				DB::table('live_pdfs')->updateOrInsert(
					['live_id' => $liveId, 'user_id' => $userId],
					[
						'pdf_path' => $pdfInfo['path'],
						'pdf_url'  => $pdfInfo['url'],
						'status'   => 'ready',
						'sent_at'  => null,
						'updated_at' => now(),
						'created_at' => now(),
					]
				);
			}
		} catch (\Throwable $e) {
			Log::error('Erro ao gerar/registrar PDF (envio individual)', [
				'live_id' => $liveId,
				'user_id' => $userId,
				'erro' => $e->getMessage(),
			]);

			return response()->json([
				'success' => false,
				'message' => 'Erro ao gerar PDF deste cliente.'
			], 500);
		}

		// 4) pega usuário + telefone (mesma regra do Encerrar Live)
		$user = DB::table('users')->where('id', $userId)->first();
		if (!$user) {
			return response()->json(['success' => false, 'message' => 'Usuário não encontrado'], 404);
		}

		$telefone = $user->whatsapp ?? $user->telefone_principal ?? $user->telefone_2;
		if (!$telefone) {
			Log::warning('Sem telefone para envio individual', [
				'live_id' => $liveId,
				'user_id' => $userId,
			]);

			DB::table('whatsapp_messages')->insert([
				'user_id' => $userId,
				'live_id' => $liveId,
				'direction' => 'outbound',
				'status' => 'failed',
				'message_type' => 'first',
				'failed_reason' => 'Sem telefone cadastrado',
				'retry_count' => 0,
				'status_updated_at' => now(),
				'created_at' => now(),
				'updated_at' => now(),
			]);

			return response()->json([
				'success' => false,
				'message' => 'Cliente sem telefone cadastrado.'
			], 422);
		}

		// Checagem de duplicidade (Msg1)
		$jaEnviouMsg1 = DB::table('whatsapp_messages')
			->where('live_id', $liveId)
			->where('user_id', $userId)
			->where('direction', 'outbound')
			->where('message_type', 'first')
			->whereIn('status', ['queued', 'sent', 'delivered']) // ajuste para os seus status reais
			->exists();

		if ($jaEnviouMsg1) {
			return response()->json([
				'success' => false,
				'already_sent' => true,
				'message' => 'A Msg1 já foi enviada para este cliente. Não vou reenviar.'
			], 200);
		}

		// 5) dispara o mesmo job do Encerrar Live
		SendWhatsAppMessage::dispatch($telefone, $liveId, $userId, 'first')
			->onQueue('whatsapp');

		return response()->json([
			'success' => true,
			'message' => 'Msg1 enfileirada para este cliente.'
		]);
	}
}