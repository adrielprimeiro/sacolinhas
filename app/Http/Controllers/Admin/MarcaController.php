<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * Display a listing of the brands.
     */
    public function index()
    {
        $marcas = Marca::orderBy('total_registros', 'desc')->orderBy('nome')->paginate(15);
        $totalMarcas = Marca::count();
        return view('admin.marcas.index', compact('marcas', 'totalMarcas'));
    }

    /**
     * Show the form for creating a new brand.
     */
    public function create()
    {
        return view('admin.marcas.form');
    }

    /**
     * Store a newly created brand in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:marcas,nome',
            'porcentagem_valor' => 'required|numeric|min:0',
        ]);

        $marca = Marca::create($validated);

        if ($request->wantsJson() || $request->has('ajax')) {
            return response()->json(['success' => true, 'marca' => $marca]);
        }

        return redirect()->route('admin.marcas.index')->with('success', 'Marca criada com sucesso!');
    }

    /**
     * Show the form for editing the specified brand.
     */
    public function edit(Marca $marca)
    {
        return view('admin.marcas.form', compact('marca'));
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(Request $request, Marca $marca)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:marcas,nome,' . $marca->id,
            'porcentagem_valor' => 'required|numeric|min:0',
        ]);

        $marca->update($validated);

        return redirect()->route('admin.marcas.index')->with('success', 'Marca atualizada com sucesso!');
    }

    /**
     * Remove the specified brand from storage.
     */
    public function destroy(Marca $marca)
    {
        // Se a marca for "Sem Marca", podemos bloquear a exclusão para evitar problemas de integridade lógica
        if (in_array(strtolower(trim($marca->nome)), ['sem marca', 'sem_marca'])) {
            return redirect()->route('admin.marcas.index')->with('error', 'A marca padrão "Sem Marca" não pode ser excluída.');
        }

        $marca->delete();

        return redirect()->route('admin.marcas.index')->with('success', 'Marca removida com sucesso!');
    }
}
