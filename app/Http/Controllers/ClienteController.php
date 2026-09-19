<?php

namespace App\Http\Controllers;

use App\Models\Cliente; // ✅ USAR MODEL CLIENTE
use App\Models\User;
use App\Models\Sacolinhas;
use App\Models\Brecho;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::clientes()->with(['limite']); // ✅ USAR SCOPE E CARREGAR LIMITE
        
        // Se for operador de brechó parceiro, filtra apenas clientes vinculados a este brechó
        if (auth()->check() && auth()->user()->isBrechoParceiro()) {
            $brechoId = auth()->user()->brecho_id;
            $query->whereExists(function ($sub) use ($brechoId) {
                $sub->select(DB::raw(1))
                    ->from('brecho_clientes')
                    ->whereColumn('brecho_clientes.user_id', 'users.id')
                    ->where('brecho_clientes.brecho_id', $brechoId);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('id', $request->user_id);
        } elseif ($request->filled('search')) {
            $search = $request->get('search');
            $query->buscar($search); // ✅ USAR SCOPE DO MODEL
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'bloqueado') {
                $query->bloqueados();
            } else {
                $query->ativos();
            }
        }
        
        if ($request->filled('cidade')) {
            $query->porCidade($request->cidade);
        }

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }

        if ($request->filled('rede_social')) {
            if ($request->rede_social === 'instagram') {
                $query->comInstagram();
            } elseif ($request->rede_social === 'whatsapp') {
                $query->comWhatsApp();
            } elseif ($request->rede_social === 'tiktok') {
                $query->comTikTok();
            }
        }

        if ($request->filled('pedidos')) {
            if ($request->pedidos === 'com') {
                $query->comPedidos();
            } elseif ($request->pedidos === 'sem') {
                $query->semPedidos();
            }
        }
        
        $clientes = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();
        $targetBrechoId = $isParceiro ? auth()->user()->brecho_id : 2;

        $clientesIncompletosCount = User::whereExists(function ($sub) use ($targetBrechoId) {
            $sub->select(DB::raw(1))
                ->from('brecho_clientes')
                ->whereColumn('brecho_clientes.user_id', 'users.id')
                ->where('brecho_clientes.brecho_id', $targetBrechoId);
        })->where(function ($q) {
            $q->where(function ($subQ) {
                $subQ->whereNull('whatsapp')->orWhere('whatsapp', '');
            })->where(function ($subQ) {
                $subQ->whereNull('phone')->orWhere('phone', '');
            });
        })->count();

        return view('admin.clientes.index', compact('clientes', 'clientesIncompletosCount', 'isParceiro'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

	public function store(Request $request)
	{
		// ✅ VALIDAÇÃO COM CAMPOS CORRETOS
		$request->validate([
			'name' => 'nullable|string|max:255',
			'instagram' => 'nullable|string|max:255',
			'tiktok' => 'nullable|string|max:255',
			'limite_credito' => 'nullable|numeric|min:0',
		], [
			'name.max' => 'O nome não pode ter mais de 255 caracteres.',
			'instagram.max' => 'O Instagram não pode ter mais de 255 caracteres.',
			'tiktok.max' => 'O TikTok não pode ter mais de 255 caracteres.',
			'limite_credito.numeric' => 'O limite de crédito deve ser um número válido.',
			'limite_credito.min' => 'O limite de crédito não pode ser negativo.',
		]);

		try {
			DB::beginTransaction();

			// ✅ LÓGICA PARA DETERMINAR O NOME (igual ao original)
			$nomeCliente = trim($request->name);
			$instagram = trim($request->instagram);
			$tiktok = trim($request->tiktok);
			
			// Se nome estiver vazio, usar Instagram ou TikTok
			if (empty($nomeCliente)) {
				if (!empty($instagram)) {
					$nomeCliente = $instagram;
				} elseif (!empty($tiktok)) {
					$nomeCliente = $tiktok;
				} else {
					// Se todos estiverem vazios, dar erro
					return redirect()->back()
								   ->withErrors(['name' => 'Preencha pelo menos o Nome, Instagram ou TikTok'])
								   ->withInput();
				}
			}
			
			// ✅ GERAÇÃO AUTOMÁTICA DE EMAIL (igual ao original)
			$nomeParaEmail = strtolower(preg_replace('/[^a-z0-9]/', '', $nomeCliente));
			$emailAutomatico = $nomeParaEmail . '@mania.com';
			
			// ✅ SALVAR COM CAMPOS CORRETOS
			$cliente = Cliente::create([
				'name' => $nomeCliente,              // Nome final
				'email' => $emailAutomatico,         // Email automático
				'password' => Hash::make('123456'),  // Senha padrão
				'role' => 'client',
				
				// ✅ CAMPOS CORRETOS PARA REDES SOCIAIS
				'instagram' => $instagram,           // Campo correto
				'tiktok' => $tiktok,                // Campo correto
				'whatsapp' => null,                 // Pode ser preenchido depois
			]);

			// Criar limite de crédito
			$limiteCredito = $request->filled('limite_credito') ? (float) $request->limite_credito : 300.00;
			\App\Models\ClienteLimite::create([
				'user_id' => $cliente->id,
				'limite_credito' => $limiteCredito,
				'limite_utilizado' => 0.00,
				'limite_disponivel' => $limiteCredito,
				'ativo' => true,
			]);

			// Vincular ao brechó caso seja parceiro
			if (auth()->check() && auth()->user()->isBrechoParceiro()) {
				DB::table('brecho_clientes')->insertOrIgnore([
					'brecho_id' => auth()->user()->brecho_id,
					'user_id' => $cliente->id,
					'origem' => 'manual',
					'created_at' => now(),
					'updated_at' => now(),
				]);
			}

			DB::commit();

			return redirect()->route('admin.clientes.index')
						   ->with('success', 'Cliente criado com sucesso!');

		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Erro ao criar cliente: ' . $e->getMessage());
			
			return redirect()->back()
						   ->with('error', 'Erro ao criar cliente.')
						   ->withInput();
		}
	}

    public function show($id)
    {
        try {
            $cliente = Cliente::clientes()->with('limite')->findOrFail($id);
            return view('admin.clientes.show', compact('cliente'));
        } catch (\Exception $e) {
            Log::error('Erro em ClienteController@show: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Cliente não encontrado.');
        }
    }

    public function edit($id)
    {
        try {
            $cliente = Cliente::clientes()->with('limite')->findOrFail($id);
            return view('admin.clientes.edit', compact('cliente'));
        } catch (\Exception $e) {
            Log::error('Erro em ClienteController@edit: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Cliente não encontrado.');
        }
    }

	public function update(Request $request, $id)
	{
		try {
			// ✅ VALIDAÇÃO COMPLETA COM MENSAGENS CUSTOMIZADAS
			$validated = $request->validate([
				// Dados Pessoais
				'name' => 'required|string|max:255',
				'apelido' => 'nullable|string|max:255',
				'data_nascimento' => 'nullable|date|before:today',
				'sexo' => 'nullable|in:M,F,Outro',
				
				// Contato
				'email' => 'required|email|max:255|unique:users,email,' . $id,
				'telefone_principal' => 'nullable|string|max:20',
				'telefone_2' => 'nullable|string|max:20',
				
				// Redes Sociais (CAMPOS CORRETOS!)
				'instagram' => 'nullable|string|max:255',
				'whatsapp' => 'nullable|string|max:255',
				'tiktok' => 'nullable|string|max:255',
				
				// Endereço
				'endereco' => 'nullable|string|max:500',
				'numero_endereco' => 'nullable|string|max:10',
				'complemento' => 'nullable|string|max:255',
				'bairro' => 'nullable|string|max:255',
				'cidade' => 'nullable|string|max:255',
				'estado' => 'nullable|string|max:2',
				'cep' => 'nullable|string|max:10',
				'pais' => 'nullable|string|max:255',
				
				// Documentos - VALIDAÇÃO MELHORADA
				'cpf' => [
					'nullable',
					'string',
					'max:14',
					'unique:users,cpf,' . $id,
					'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/', // CPF formatado ou só números
				],
				'rg' => 'nullable|string|max:20',
				
				// Comercial
				'codigo_cliente' => 'nullable|integer',
				'tipo_cliente' => 'nullable|string|max:100',
				'observacao_cliente' => 'nullable|string',
				'limite_credito' => 'nullable|numeric|min:0',
				
				// Segurança
				'password' => 'nullable|string|min:6|confirmed',
				'role' => 'nullable|in:client,admin',
				'is_admin' => 'nullable|boolean',
			], [
				// ✅ MENSAGENS CUSTOMIZADAS
				'name.required' => 'O nome é obrigatório.',
				'name.max' => 'O nome não pode ter mais de 255 caracteres.',
				
				'email.required' => 'O email é obrigatório.',
				'email.email' => 'Digite um email válido.',
				'email.unique' => 'Este email já está sendo usado por outro cliente.',
				
				'cpf.unique' => 'Este CPF já está cadastrado para outro cliente.',
				'cpf.regex' => 'Digite um CPF válido (000.000.000-00).',
				
				'data_nascimento.date' => 'Digite uma data válida.',
				'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
				
				'sexo.in' => 'Sexo deve ser Masculino, Feminino ou Outro.',
				
				'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
				'password.confirmed' => 'A confirmação da senha não confere.',
				
				'role.in' => 'Função deve ser Cliente ou Administrador.',
				'limite_credito.numeric' => 'O limite de crédito deve ser um número válido.',
				'limite_credito.min' => 'O limite de crédito não pode ser negativo.',
			]);

			DB::beginTransaction();

			$cliente = Cliente::clientes()->findOrFail($id);
			
			// Dados para atualizar
			$updateData = $validated;
			
			// ✅ Tratar senha separadamente
			if ($request->filled('password')) {
				$updateData['password'] = Hash::make($request->password);
			} else {
				unset($updateData['password']); // Remove se vazio
			}

			// ✅ Tratar checkbox bloqueado
			$updateData['bloqueado'] = $request->has('bloqueado');

			// ✅ Executar atualização
			$cliente->update($updateData);

			// Atualizar limite de crédito
			$limiteCredito = $request->filled('limite_credito') ? (float) $request->limite_credito : 300.00;
			$limite = \App\Models\ClienteLimite::where('user_id', $cliente->id)->first();
			if ($limite) {
				$diferenca = $limiteCredito - $limite->limite_credito;
				$limite->update([
					'limite_credito' => $limiteCredito,
					'limite_disponivel' => $limite->limite_disponivel + $diferenca,
					'data_ultimo_ajuste' => now(),
					'motivo_ultimo_ajuste' => 'Ajuste de limite via painel',
				]);
			} else {
				\App\Models\ClienteLimite::create([
					'user_id' => $cliente->id,
					'limite_credito' => $limiteCredito,
					'limite_utilizado' => 0.00,
					'limite_disponivel' => $limiteCredito,
					'ativo' => true,
				]);
			}
			
			DB::commit();

			return redirect()->route('admin.clientes.show', $cliente->id)
						   ->with('success', 'Cliente atualizado com sucesso!');

		} catch (\Illuminate\Validation\ValidationException $e) {
			// ✅ ERRO DE VALIDAÇÃO - RETORNA COM ERROS
			return redirect()->back()
						   ->withErrors($e->validator)
						   ->withInput()
						   ->with('error', 'Corrija os erros abaixo e tente novamente.');
						   
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('Erro ao atualizar cliente: ' . $e->getMessage());
			
			return redirect()->back()
						   ->with('error', 'Erro interno: ' . $e->getMessage())
						   ->withInput();
		}
	}

    public function destroy($id)
    {
        try {
            // Busca o cliente (usando o escopo 'clientes' se aplicável)
            $cliente = Cliente::clientes()->findOrFail($id);
            
            // ✅ MUDANÇA: Exclusão real do registro
            $cliente->delete();
            
            return redirect()->route('admin.clientes.index')
                            ->with('success', 'Cliente excluído com sucesso!');
                            
        } catch (\Illuminate\Database\QueryException $e) {
            // Captura erro específico de chave estrangeira (caso o cliente tenha sacolinhas/pedidos)
            Log::error('Erro de integridade ao excluir cliente: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Não é possível excluir este cliente pois ele possui registros vinculados (sacolinhas, pedidos, etc).');
                            
        } catch (\Exception $e) {
            Log::error('Erro em ClienteController@destroy: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Erro ao excluir cliente.');
        }
    }
	
	
    public function toggleBlock($id)
    {
        try {
            $cliente = Cliente::clientes()->findOrFail($id);
            
            if ($cliente->isBloqueado()) {
                $cliente->desbloquear();
                $message = 'Cliente desbloqueado com sucesso!';
            } else {
                $cliente->bloquear(); 
                $message = 'Cliente bloqueado com sucesso!';
            }
            
            return redirect()->back()->with('success', $message);
                            
        } catch (\Exception $e) {
            Log::error('Erro em ClienteController@toggleBlock: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Erro ao alterar status do cliente.');
        }
    }
    
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Query parameter is required',
                'data' => []
            ]);
        }

        try {
            $queryBuilder = Cliente::clientes();

            // Se for operador de brechó parceiro, filtra apenas clientes vinculados a este brechó
            if (auth()->check() && auth()->user()->isBrechoParceiro()) {
                $brechoId = auth()->user()->brecho_id;
                $queryBuilder->whereExists(function ($sub) use ($brechoId) {
                    $sub->select(DB::raw(1))
                        ->from('brecho_clientes')
                        ->whereColumn('brecho_clientes.user_id', 'users.id')
                        ->where('brecho_clientes.brecho_id', $brechoId);
                });
            }

            $clientes = $queryBuilder->buscar($query) // ✅ USAR SCOPE
                             ->limit(10)
                             ->get([
                                'id', 
                                'name', 
                                'email', 
                                'apelido',
                                'instagram', 
                                'tiktok', 
                                'whatsapp',
                                'codigo_cliente',
                                'cpf'
                             ]);

            $clientesData = $clientes->map(function ($c) {
                $hasAssinatura = \Illuminate\Support\Facades\DB::table('clube_assinaturas')
                                   ->where('user_id', $c->id)
                                   ->where('status', 'ativa')
                                   ->exists();
                
                $c->tipo_cliente = $hasAssinatura ? 'clube' : 'fora_clube';
                return $c;
            });

            return response()->json([
                'success' => true,
                'data' => $clientesData,
                'search_term' => $query
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na busca de clientes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'data' => []
            ], 500);
        }
    }

    /**
     * Tela de vinculação e transferência de clientes da Minha Mania para o brechó parceiro.
     */
    public function vincularMania(Request $request)
    {
        $user = auth()->user();
        $isParceiro = $user && $user->isBrechoParceiro();
        
        $brechoId = $isParceiro ? $user->brecho_id : (int) $request->get('brecho_id', 2);
        $brecho = Brecho::find($brechoId) ?? Brecho::first();

        if (!$brecho) {
            return redirect()->route('admin.clientes.index')->with('error', 'Nenhum brechó parceiro encontrado.');
        }

        // Buscar clientes vinculados a este brechó
        $clientes = User::whereExists(function ($sub) use ($brechoId) {
            $sub->select(DB::raw(1))
                ->from('brecho_clientes')
                ->whereColumn('brecho_clientes.user_id', 'users.id')
                ->where('brecho_clientes.brecho_id', $brechoId);
        })
        ->withCount(['sacolinhas as sacolinhas_brecho_count' => function ($q) use ($brechoId) {
            $q->where('brecho_id', $brechoId);
        }])
        ->orderBy('sacolinhas_brecho_count', 'desc')
        ->orderBy('name', 'asc')
        ->get();

        // Para cada cliente, identificar se é incompleto e buscar sugestões da Mania
        $clientesComSugestoes = $clientes->map(function ($c) use ($brechoId) {
            $emailAuto = str_ends_with($c->email, '@mania.com') || str_ends_with($c->email, '@temp.cliente.com');
            $isIncompleto = (empty($c->whatsapp) && empty($c->phone)) || $emailAuto;
            $c->is_incompleto = $isIncompleto;

            // Busca sugestões inteligentes na base da Mania (excluindo o próprio dummy)
            $c->sugestoes = $this->gerarSugestoesMania($c->name);

            return $c;
        });

        $todosBrechos = Brecho::where('ativo', true)->get();

        return view('admin.clientes.vincular_mania', compact(
            'brecho',
            'brechoId',
            'clientesComSugestoes',
            'todosBrechos',
            'isParceiro'
        ));
    }

    /**
     * Busca inteligente na base de clientes da Minha Mania (para AJAX).
     */
    public function buscarMania(Request $request)
    {
        $query = trim($request->get('q', ''));
        $targetBrechoId = (int) $request->get('brecho_id', 2);

        if (mb_strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        try {
            $cleanPhone = preg_replace('/\D/', '', $query);

            $candidates = User::where('role', 'client')
                ->where(function ($q) use ($query, $cleanPhone) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('nome_cliente', 'like', "%{$query}%")
                      ->orWhere('apelido', 'like', "%{$query}%")
                      ->orWhere('instagram', 'like', "%{$query}%")
                      ->orWhere('cpf', 'like', "%{$query}%");

                    if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                        $q->orWhere('whatsapp', 'like', "%{$cleanPhone}%")
                          ->orWhere('phone', 'like', "%{$cleanPhone}%");
                    }
                })
                ->limit(20)
                ->get();

            $jaVinculados = DB::table('brecho_clientes')
                ->where('brecho_id', $targetBrechoId)
                ->whereIn('user_id', $candidates->pluck('id'))
                ->pluck('user_id')
                ->toArray();

            $data = $candidates->map(function ($c) use ($jaVinculados) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'apelido' => $c->apelido,
                    'email' => $c->email,
                    'whatsapp' => $c->whatsapp ?: $c->phone,
                    'instagram' => $c->instagram,
                    'cidade' => $c->cidade,
                    'estado' => $c->estado,
                    'cpf' => $c->cpf,
                    'total_pedidos' => $c->total_pedidos,
                    'ja_vinculado' => in_array($c->id, $jaVinculados),
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar clientes da Mania: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Transfere sacolinhas e vincula o cliente real da Mania ao brechó parceiro,
     * limpando o cadastro temporário/dummy.
     */
    public function transferirDadosMania(Request $request)
    {
        $request->validate([
            'dummy_user_id' => 'required|integer',
            'mania_user_id' => 'required|integer|exists:users,id',
            'brecho_id' => 'nullable|integer',
        ]);

        $user = auth()->user();
        $brechoId = ($user && $user->isBrechoParceiro()) ? $user->brecho_id : (int) ($request->brecho_id ?: 2);
        $dummyUserId = (int) $request->dummy_user_id;
        $maniaUserId = (int) $request->mania_user_id;

        try {
            $sacolasTransferidas = 0;

            DB::transaction(function () use ($brechoId, $dummyUserId, $maniaUserId, &$sacolasTransferidas) {
                // 1. Garante o vínculo do cliente real da Mania em brecho_clientes
                DB::table('brecho_clientes')->updateOrInsert(
                    ['brecho_id' => $brechoId, 'user_id' => $maniaUserId],
                    ['origem' => 'vinculo_mania', 'updated_at' => now()]
                );

                // 2. Transfere sacolinhas deste brechó do dummy para o cliente real
                $sacolasTransferidas = Sacolinhas::where('brecho_id', $brechoId)
                    ->where('user_id', $dummyUserId)
                    ->update(['user_id' => $maniaUserId]);

                // 3. Transfere pedidos deste brechó se houver
                DB::table('pedidos')
                    ->where('brecho_id', $brechoId)
                    ->where('user_id', $dummyUserId)
                    ->update(['user_id' => $maniaUserId]);

                // 4. Se o usuário dummy for diferente do real, desvincula do brecho_clientes
                if ($dummyUserId !== $maniaUserId) {
                    DB::table('brecho_clientes')
                        ->where('brecho_id', $brechoId)
                        ->where('user_id', $dummyUserId)
                        ->delete();

                    // Se não tiver mais nenhuma sacolinha e nenhum outro brechó, remove o dummy para manter o banco limpo
                    $sacolinhasRestantes = Sacolinhas::where('user_id', $dummyUserId)->count();
                    $brechosRestantes = DB::table('brecho_clientes')->where('user_id', $dummyUserId)->count();
                    
                    if ($sacolinhasRestantes === 0 && $brechosRestantes === 0) {
                        \App\Models\ClienteLimite::where('user_id', $dummyUserId)->delete();
                        User::where('id', $dummyUserId)->delete();
                    }
                }
            });

            $clienteReal = User::find($maniaUserId);

            return response()->json([
                'success' => true,
                'message' => "Cliente {$clienteReal->name} vinculado com sucesso e {$sacolasTransferidas} peça(s) transferida(s)!",
                'cliente' => [
                    'id' => $clienteReal->id,
                    'name' => $clienteReal->name,
                    'whatsapp' => $clienteReal->whatsapp ?: $clienteReal->phone,
                    'cidade' => $clienteReal->cidade,
                    'estado' => $clienteReal->estado,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao transferir dados da Mania: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa diretamente um cliente da Mania para o brechó parceiro (sem precisar de cadastro prévio).
     */
    public function importarMania(Request $request)
    {
        $request->validate([
            'mania_user_id' => 'required|integer|exists:users,id',
            'brecho_id' => 'nullable|integer',
        ]);

        $user = auth()->user();
        $brechoId = ($user && $user->isBrechoParceiro()) ? $user->brecho_id : (int) ($request->brecho_id ?: 2);
        $maniaUserId = (int) $request->mania_user_id;

        try {
            DB::table('brecho_clientes')->updateOrInsert(
                ['brecho_id' => $brechoId, 'user_id' => $maniaUserId],
                ['origem' => 'importacao_mania', 'updated_at' => now()]
            );

            $clienteReal = User::find($maniaUserId);

            return response()->json([
                'success' => true,
                'message' => "Cliente {$clienteReal->name} importado e vinculado ao brechó com sucesso!",
                'cliente' => [
                    'id' => $clienteReal->id,
                    'name' => $clienteReal->name,
                    'whatsapp' => $clienteReal->whatsapp ?: $clienteReal->phone,
                    'cidade' => $clienteReal->cidade,
                    'estado' => $clienteReal->estado,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao importar cliente da Mania: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper privado para gerar sugestões de clientes Mania com base no nome digitado.
     */
    private function gerarSugestoesMania(string $name): array
    {
        $cleanName = trim($name);
        $words = array_filter(explode(' ', $cleanName), fn($w) => mb_strlen($w) >= 3);

        $candidates = User::where('role', 'client')
            ->where(function ($q) use ($words, $cleanName) {
                foreach ($words as $w) {
                    $q->orWhere('name', 'like', "%{$w}%")
                      ->orWhere('nome_cliente', 'like', "%{$w}%")
                      ->orWhere('apelido', 'like', "%{$w}%")
                      ->orWhere('instagram', 'like', "%{$w}%");
                }
                // Variações fonéticas conhecidas
                if (stripos($cleanName, 'rock') !== false) {
                    $q->orWhere('name', 'like', '%Roque%')->orWhere('instagram', 'like', '%roque%');
                }
                if (stripos($cleanName, 'jane') !== false) {
                    $q->orWhere('name', 'like', '%Jany%')->orWhere('apelido', 'like', '%Jany%');
                }
                if (stripos($cleanName, 'tacia') !== false) {
                    $q->orWhere('name', 'like', '%Taci%');
                }
                if (stripos($cleanName, 'concei') !== false) {
                    $q->orWhere('name', 'like', '%Concei%');
                }
            })
            ->select('id', 'name', 'apelido', 'whatsapp', 'phone', 'instagram', 'cidade', 'estado', 'cpf')
            ->limit(50)
            ->get();

        $normDummy = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $cleanName) ?: $cleanName));

        $scored = $candidates->map(function ($c) use ($normDummy) {
            $score = 0;
            $normC = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $c->name) ?: $c->name));
            if ($normC === $normDummy) $score += 100;
            elseif (!empty($normDummy) && (str_contains($normC, $normDummy) || str_contains($normDummy, $normC))) $score += 50;

            if (in_array(strtoupper($c->estado ?? ''), ['SC', 'SANTA CATARINA'])) $score += 30;
            if (preg_match('/(florian|jose|biguac|palhoca)/i', $c->cidade ?? '')) $score += 30;

            if ($c->whatsapp) $score += 15;
            if ($c->instagram) $score += 10;

            $c->score = $score;
            return $c;
        })->sortByDesc('score')->take(3)->values();

        return $scored->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'apelido' => $c->apelido,
                'whatsapp' => $c->whatsapp ?: $c->phone,
                'instagram' => $c->instagram,
                'cidade' => $c->cidade,
                'estado' => $c->estado,
                'score' => $c->score,
            ];
        })->toArray();
    }
}