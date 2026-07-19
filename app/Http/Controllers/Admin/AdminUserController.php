<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefone', 'like', "%{$search}%");
            });
        }

        // Ordenar admins primeiro, depois por nome
        $users = $query->orderBy('is_admin', 'desc')
                       ->orderBy('name', 'asc')
                       ->paginate(50);

        return view('admin.users.permissions', compact('users'));
    }

    public function updateRole(Request $request, $id)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $user = User::findOrFail($id);

        $request->validate([
            'role' => 'required|in:client,admin,admin_master',
        ]);

        $role = $request->input('role');
        
        $user->role = $role;
        
        if ($role === 'admin' || $role === 'admin_master') {
            $user->is_admin = 1;
        } else {
            $user->is_admin = 0;
        }

        $user->save();

        return redirect()->back()->with('success', "Permissão de {$user->name} atualizada com sucesso!");
    }

    public function update(Request $request, $id)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telefone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->telefone = $request->input('telefone');

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()->back()->with('success', "Dados de {$user->name} atualizados com sucesso!");
    }
}
