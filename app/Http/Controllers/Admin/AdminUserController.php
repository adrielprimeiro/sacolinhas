<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $query = User::query();

        $filter = $request->get('filter', $request->filled('search') ? 'all' : 'equipe');

        if ($filter === 'equipe') {
            $query->where(function ($q) {
                $q->whereIn('role', ['admin', 'admin_master', 'brecho_admin'])
                  ->orWhere('is_admin', 1);
            });
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        // Ordenar admins primeiro, depois por nome
        $users = $query->orderByRaw("FIELD(role, 'admin_master', 'admin', 'brecho_admin', 'client')")
                       ->orderBy('is_admin', 'desc')
                       ->orderBy('name', 'asc')
                       ->paginate(50)
                       ->withQueryString();

        $brechos = \App\Models\Brecho::where('ativo', true)->get();
        $modules = User::availableModules();

        return view('admin.users.permissions', compact('users', 'brechos', 'modules', 'filter'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'whatsapp'    => 'nullable|string|max:20',
            'password'    => 'required|string|min:6',
            'role'        => 'required|in:admin,admin_master,brecho_admin,client',
            'brecho_id'   => 'nullable|exists:brechos,id',
            'permissions' => 'nullable|array',
        ]);

        $role = $request->input('role', 'admin');
        $availableModules = array_keys(User::availableModules());
        $permissions = null;

        if ($role === 'admin' && $request->has('permissions') && is_array($request->input('permissions'))) {
            $permissions = [];
            foreach ($availableModules as $mod) {
                $val = $request->input("permissions.{$mod}", 'none');
                $permissions[$mod] = in_array($val, ['none', 'read', 'write'], true) ? $val : 'none';
            }
        }

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->whatsapp = $request->input('whatsapp');
        $user->password = Hash::make($request->input('password'));
        $user->role = $role;
        $user->is_admin = in_array($role, ['admin', 'admin_master'], true) ? 1 : 0;
        $user->brecho_id = $request->input('brecho_id') ?: 1;
        $user->bloqueado = 0;
        $user->permissions = $permissions;
        $user->save();

        return redirect()->route('admin.equipe.index', ['filter' => 'equipe'])
            ->with('success', "Login de equipe para {$user->name} ({$user->email}) criado com sucesso!");
    }

    public function updateRole(Request $request, $id)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $user = User::findOrFail($id);

        $request->validate([
            'role'      => 'required|in:client,admin,admin_master,brecho_admin',
            'brecho_id' => 'nullable|exists:brechos,id',
        ]);

        $role = $request->input('role');
        $user->role = $role;
        $user->is_admin = in_array($role, ['admin', 'admin_master'], true) ? 1 : 0;

        if ($request->has('brecho_id')) {
            $user->brecho_id = $request->input('brecho_id') ?: null;
        }

        $user->save();

        return redirect()->back()->with('success', "Nível de acesso de {$user->name} atualizado com sucesso!");
    }

    public function updatePermissions(Request $request, $id)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $user = User::findOrFail($id);

        if ($request->boolean('reset_full_access')) {
            $user->permissions = null;
            if ($user->role === 'client') {
                $user->role = 'admin';
                $user->is_admin = 1;
            }
            $user->save();

            return redirect()->back()->with('success', "Permissões de {$user->name} restauradas para Acesso Total!");
        }

        $availableModules = array_keys(User::availableModules());
        $permissions = [];

        foreach ($availableModules as $mod) {
            $val = $request->input("permissions.{$mod}", 'none');
            $permissions[$mod] = in_array($val, ['none', 'read', 'write'], true) ? $val : 'none';
        }

        // Se o usuário era cliente e está recebendo permissões de equipe, promove para 'admin' (Operador/Atendente)
        if ($user->role === 'client') {
            $user->role = 'admin';
            $user->is_admin = 1;
        }

        $user->permissions = $permissions;
        $user->save();

        return redirect()->back()->with('success', "Permissões por módulo de {$user->name} salvas com sucesso!");
    }

    public function update(Request $request, $id)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso negado. Apenas Master.');

        $user = User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $user->id,
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->whatsapp = $request->input('whatsapp');

        if ($request->has('bloqueado')) {
            $user->bloqueado = $request->boolean('bloqueado') ? 1 : 0;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()->back()->with('success', "Dados de {$user->name} atualizados com sucesso!");
    }
}
