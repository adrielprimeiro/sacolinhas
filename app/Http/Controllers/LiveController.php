<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Live; 
use App\Models\User;

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
     * Encerrar live
     */
    public function destroy($id)
    {
        try {
            $live = DB::table('lives')->where('id', $id)->first();
            
            if (!$live) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live não encontrada'
                ], 404);
            }

            if (!$live->ativo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta live já foi encerrada'
                ], 400);
            }

            // Encerrar live (marcar como inativa)
            DB::table('lives')
                ->where('id', $id)
                ->update([
                    'ativo' => false,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Live encerrada com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao encerrar live: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao encerrar live: ' . $e->getMessage()
            ], 500);
        }
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
}