<?php

namespace App\Http\Controllers;

use App\Models\Cliente; // ✅ USAR MODEL CLIENTE
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::clientes()->with([]); // ✅ USAR SCOPE
        
        if ($request->filled('search')) {
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
        
        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.clientes.index', compact('clientes'));
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
		], [
			'name.max' => 'O nome não pode ter mais de 255 caracteres.',
			'instagram.max' => 'O Instagram não pode ter mais de 255 caracteres.',
			'tiktok.max' => 'O TikTok não pode ter mais de 255 caracteres.',
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
			Cliente::create([
				'name' => $nomeCliente,              // Nome final
				'email' => $emailAutomatico,         // Email automático
				'password' => Hash::make('123456'),  // Senha padrão
				'role' => 'client',
				
				// ✅ CAMPOS CORRETOS PARA REDES SOCIAIS
				'instagram' => $instagram,           // Campo correto
				'tiktok' => $tiktok,                // Campo correto
				'whatsapp' => null,                 // Pode ser preenchido depois
			]);

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
            $cliente = Cliente::clientes()->findOrFail($id);
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
            $cliente = Cliente::clientes()->findOrFail($id);
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
            $clientes = Cliente::clientes()
                             ->buscar($query) // ✅ USAR SCOPE
                             ->limit(10)
                             ->get([
                                'id', 
                                'name', 
                                'email', 
                                'apelido',
                                'instagram', 
                                'tiktok', 
                                'whatsapp',
                                'codigo_cliente'
                             ]);

            return response()->json([
                'success' => true,
                'data' => $clientes,
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
}