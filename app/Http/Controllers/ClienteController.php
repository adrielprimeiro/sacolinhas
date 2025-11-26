<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'client');
        
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nome_cliente', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%");
            });
        }
        
        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

	public function store(Request $request)
	{
		// Lógica para determinar o nome do cliente
		$nomeCliente = trim($request->nome_cliente);
		$instagram = trim($request->ig_instagram);
		$tiktok = trim($request->ig_tiktok);
		
		// Se nome estiver vazio, usar Instagram ou TikTok
		if (empty($nomeCliente)) {
			if (!empty($instagram)) {
				$nomeCliente = $instagram;
			} elseif (!empty($tiktok)) {
				$nomeCliente = $tiktok;
			} else {
				// Se todos estiverem vazios, dar erro
				return redirect()->back()
							   ->withErrors(['nome_cliente' => 'Preencha pelo menos o Nome, Instagram ou TikTok'])
							   ->withInput();
			}
		}
		
		// Geração automática de email baseada no nome final
		$nomeParaEmail = strtolower(preg_replace('/[^a-z0-9]/', '', $nomeCliente));
		$emailAutomatico = $nomeParaEmail . '@mania.com';
		
		User::create([
			'name' => $nomeCliente,                          // Nome final (preenchido ou automático)
			'nome_cliente' => $request->ig_tiktok,           // TikTok
			'remember_token' => $request->ig_instagram,      // Instagram
			'email' => $emailAutomatico,                     // Email automático
			'password' => Hash::make('123456'),              // Senha padrão
			'role' => 'client',
		]);

		return redirect()->route('admin.clientes.index')
						->with('success', 'Cliente criado com sucesso!');
	}

    public function show($id)
    {
        try {
            $cliente = User::where('role', 'client')->findOrFail($id);
            return view('admin.clientes.show', compact('cliente'));
        } catch (\Exception $e) {
            \Log::error('Erro em ClienteController@show: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Cliente não encontrado.');
        }
    }

    public function edit($id)
    {
        try {
            $cliente = User::where('role', 'client')->findOrFail($id);
            return view('admin.clientes.edit', compact('cliente'));
        } catch (\Exception $e) {
            \Log::error('Erro em ClienteController@edit: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Cliente não encontrado.');
        }
    }


	public function update(Request $request, $id)
	{
		// 🔥 LOG DETALHADO
		\Log::info('=== UPDATE INICIADO ===');
		\Log::info('Servidor: ' . gethostname());
		\Log::info('PHP Version: ' . phpversion());
		\Log::info('Timezone: ' . date_default_timezone_get());
		\Log::info('Request Method: ' . $request->method());
		\Log::info('Request URL: ' . $request->url());
		\Log::info('Cliente ID: ' . $id);
		\Log::info('Request Data:', $request->all());
		
		try {
			$request->validate([
				'name' => 'required|string|max:255',
			]);

			$cliente = User::where('role', 'client')->findOrFail($id);
			
			\Log::info('Cliente encontrado:', $cliente->toArray());
			
			$updateData = [
				'name' => $request->name,
				'bloqueado' => $request->has('bloqueado') ? 1 : 0,
			];
			
			\Log::info('Dados para atualizar:', $updateData);

			$cliente->update($updateData);
			
			\Log::info('✅ Cliente atualizado com sucesso!');

			return redirect()->route('admin.clientes.show', $cliente->id)
							->with('success', 'Cliente atualizado com sucesso!');

		} catch (\Exception $e) {
			\Log::error('❌ ERRO ao atualizar cliente: ' . $e->getMessage());
			\Log::error('Stack: ' . $e->getTraceAsString());
			
			return redirect()->back()
							->with('error', 'Erro ao atualizar cliente: ' . $e->getMessage())
							->withInput();
		}
	}

/*	public function update(Request $request, $id)
	{
		try {
			// VALIDAÇÃO COMPLETA DE TODOS OS CAMPOS
			$request->validate([
				// Dados Pessoais
				'name' => 'required|string|max:255',
				'apelido' => 'nullable|string|max:255',
				//'data_nascimento' => 'nullable|date',
				//'sexo' => 'nullable|in:M,F',
				//'birth_date' => 'nullable|date',
				
				// Redes Sociais
				'remember_token' => 'nullable|string|max:255', // Instagram
				'nome_cliente' => 'nullable|string|max:255',   // TikTok
				
				// Contato
				'email' => 'required|email|max:255|unique:users,email,' . $id,
				'telefone_principal' => 'nullable|string|max:20',
				'telefone_2' => 'nullable|string|max:20',
				'phone' => 'nullable|string|max:20', // WhatsApp
				
				// Endereço
				'endereco' => 'nullable|string|max:500',
				'numero_endereco' => 'nullable|string|max:10',
				'complemento' => 'nullable|string|max:255',
				'bairro' => 'nullable|string|max:255',
				'cidade' => 'nullable|string|max:255',
				'estado' => 'nullable|string|max:2',
				'cep' => 'nullable|string|max:10',
				'pais' => 'nullable|string|max:255',
				
				// Documentos
				'cpf' => 'nullable|string|max:14',
				'rg' => 'nullable|string|max:20',
				
				// Comercial
				'codigo_cliente' => 'nullable|string|max:50',
				'tipo_cliente' => 'nullable|string|max:100',
				'observacao_cliente' => 'nullable|text',
				
				// Segurança
				'password' => 'nullable|string|min:6|confirmed',
				'role' => 'nullable|in:client,admin',
				'is_admin' => 'nullable|boolean',
			]);

			$cliente = User::where('role', 'client')->findOrFail($id);

			// TODOS OS CAMPOS DO FORMULÁRIO
			$updateData = [
				// 📋 Dados Pessoais
				'name' => $request->name,
				'apelido' => $request->apelido,
				//'data_nascimento' => $request->data_nascimento,
				//'sexo' => $request->sexo,
				//'birth_date' => $request->birth_date,
				
				// 📱 Redes Sociais (CORRIGIDO!)
				'remember_token' => $request->remember_token, // Instagram
				'nome_cliente' => $request->nome_cliente,     // TikTok
				'phone' => $request->phone,                   // WhatsApp
				
				// 📞 Contato
				'email' => $request->email,
				'telefone_principal' => $request->telefone_principal,
				'telefone_2' => $request->telefone_2,
				
				// 🏠 Endereço
				'endereco' => $request->endereco,
				'numero_endereco' => $request->numero_endereco,
				'complemento' => $request->complemento,
				'bairro' => $request->bairro,
				'cidade' => $request->cidade,
				'estado' => $request->estado,
				'cep' => $request->cep,
				'pais' => $request->pais,
				
				// 📄 Documentos
				'cpf' => $request->cpf,
				'rg' => $request->rg,
				
				// 💼 Comercial
				'codigo_cliente' => $request->codigo_cliente,
				'tipo_cliente' => $request->tipo_cliente,
				'observacao_cliente' => $request->observacao_cliente,
				
				// 🔒 Segurança
				'role' => $request->role ?? 'client',
				'is_admin' => $request->boolean('is_admin'),
				
				// ⚡ Status
				'bloqueado' => $request->has('bloqueado') ? 1 : 0,
			];

			// Senha (se fornecida)
			if ($request->filled('password')) {
				$updateData['password'] = Hash::make($request->password);
			}

			// Executar atualização
			$cliente->update($updateData);

			return redirect()->route('admin.clientes.show', $cliente->id)
							->with('success', 'Cliente atualizado com sucesso!');

		} catch (\Exception $e) {
			\Log::error('Erro ao atualizar cliente: ' . $e->getMessage());
			return redirect()->back()
							->with('error', 'Erro ao atualizar cliente: ' . $e->getMessage())
							->withInput();
		}
	}

    public function destroy($id)
    {
        try {
            $cliente = User::where('role', 'client')->findOrFail($id);
            $cliente->delete();
            return redirect()->route('admin.clientes.index')
                            ->with('success', 'Cliente removido com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro em ClienteController@destroy: ' . $e->getMessage());
            return redirect()->route('admin.clientes.index')
                            ->with('error', 'Erro ao remover cliente.');
        }
    }

    // MÉTODO QUE ESTAVA FALTANDO!
    public function toggleBlock($id)
    {
        try {
            $cliente = User::where('role', 'client')->findOrFail($id);
            $cliente->bloqueado = !$cliente->bloqueado;
            $cliente->save();

            $status = $cliente->bloqueado ? 'bloqueado' : 'desbloqueado';
            
            return redirect()->back()
                            ->with('success', "Cliente {$status} com sucesso!");
                            
        } catch (\Exception $e) {
            \Log::error('Erro em ClienteController@toggleBlock: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Erro ao alterar status do cliente.');
        }
    }
		
	public function search(Request $request)
	{
		$query = $request->get('q');
		$role = $request->get('role', 'client');
		
		if (!$query) {
			return response()->json([
				'success' => false,
				'message' => 'Query parameter is required',
				'data' => []
			]);
		}

		try {
			$users = User::where(function($q) use ($query) {
							$q->where('name', 'like', "%{$query}%")
							  ->orWhere('email', 'like', "%{$query}%")
							  ->orWhere('remember_token', 'like', "%{$query}%")
							  ->orWhere('nome_cliente', 'like', "%{$query}%")
							  ->orWhere('apelido', 'like', "%{$query}%")
							  ->orWhere('id', $query);
						})
						->where('role', $role) // Ajuste conforme sua estrutura
						->limit(10)
						->get([
							'id', 
							'name', 
							'email', 
							'avatar_url', 
							'remember_token', 
							'nome_cliente', 
							'apelido'
						]);

			return response()->json([
				'success' => true,
				'data' => $users,
				'search_term' => $query
			]);

		} catch (\Exception $e) {
			\Log::error('Erro na busca de clientes: ' . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro interno do servidor',
				'data' => []
			], 500);
		}
	}*/
}
