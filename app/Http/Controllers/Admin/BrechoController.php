<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brecho;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrechoController extends Controller
{
    public function index()
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $brechos = Brecho::withCount(['items', 'lives', 'users'])->with('users')->orderBy('id', 'asc')->get();

        return view('admin.brechos.index', compact('brechos'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'chave_pix' => 'nullable|string|max:255',
            'tipo_chave_pix' => 'nullable|string|in:cpf,cnpj,email,telefone,aleatoria',
            'operador_nome' => 'nullable|string|max:255',
            'operador_email' => 'nullable|email|max:255|unique:users,email',
            'operador_password' => 'nullable|string|min:6',
        ]);

        $slug = Str::slug($validated['nome']);
        // Garante slug único
        $originalSlug = $slug;
        $count = 1;
        while (Brecho::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        $brechoData = [
            'nome' => $validated['nome'],
            'documento' => $validated['documento'] ?? null,
            'telefone' => $validated['telefone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'chave_pix' => $validated['chave_pix'] ?? null,
            'tipo_chave_pix' => $validated['tipo_chave_pix'] ?? null,
            'slug' => $slug,
            'ativo' => true,
        ];

        $brecho = Brecho::create($brechoData);

        if (!empty($validated['operador_email']) && !empty($validated['operador_password'])) {
            $operador = \App\Models\User::create([
                'name' => $validated['operador_nome'] ?: $validated['nome'],
                'email' => $validated['operador_email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['operador_password']),
                'whatsapp' => $validated['whatsapp'] ?? null,
                'role' => 'brecho_admin',
                'brecho_id' => $brecho->id,
                'is_admin' => 0,
            ]);

            return redirect()->back()->with('success', "Brechó '{$brecho->nome}' e usuário de acesso '{$operador->email}' criados com sucesso!");
        }

        return redirect()->back()->with('success', "Brechó '{$brecho->nome}' cadastrado com sucesso! Crie o acesso de login clicando em '+ Operador'.");
    }

    public function update(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'chave_pix' => 'nullable|string|max:255',
            'tipo_chave_pix' => 'nullable|string|in:cpf,cnpj,email,telefone,aleatoria',
            'ativo' => 'required|boolean',
        ]);

        $brecho->update($validated);

        return redirect()->back()->with('success', "Brechó '{$brecho->nome}' atualizado com sucesso!");
    }

    public function createOperator(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'whatsapp' => $validated['whatsapp'] ?? null,
            'role' => 'brecho_admin',
            'brecho_id' => $brecho->id,
            'is_admin' => 0,
        ]);

        return redirect()->back()->with('success', "Usuário '{$user->name}' ({$user->email}) criado com sucesso para o brechó '{$brecho->nome}'!");
    }
}
