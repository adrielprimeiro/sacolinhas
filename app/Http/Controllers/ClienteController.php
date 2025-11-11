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
        try {
            $cliente = User::where('role', 'client')->findOrFail($id);
            
            $request->validate([
            ]);

            $dados = [
                'nome_cliente' => $request->nome_cliente,
                'name' => $request->nome_cliente,
                'email' => $request->email,
                'cpf' => $request->cpf,
                'telefone_principal' => $request->telefone_principal,
                'data_nascimento' => $request->data_nascimento,
                'sexo' => $request->sexo,
                'bloqueado' => $request->has('bloqueado'),
            ];
            
            if ($request->filled('password')) {
                $dados['password'] = Hash::make($request->password);
            }

            $cliente->update($dados);

            return redirect()->route('admin.clientes.show', $cliente)
                            ->with('success', 'Cliente atualizado com sucesso!');
                            
        } catch (\Exception $e) {
            \Log::error('Erro em ClienteController@update: ' . $e->getMessage());
            return redirect()->back()
                            ->withErrors(['error' => 'Erro ao atualizar cliente: ' . $e->getMessage()])
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
}
