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

        $brechos = Brecho::withCount(['items', 'lives', 'users'])->orderBy('id', 'asc')->get();

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
        ]);

        $slug = Str::slug($validated['nome']);
        // Garante slug único
        $originalSlug = $slug;
        $count = 1;
        while (Brecho::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }
        $validated['slug'] = $slug;
        $validated['ativo'] = true;

        Brecho::create($validated);

        return redirect()->back()->with('success', "Brechó '{$validated['nome']}' cadastrado com sucesso!");
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
}
