<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email' => 'required|email',
			'password' => 'required',
		]);

		// Adicionar verificação de bloqueado
		$credentials = array_merge($credentials, ['bloqueado' => 0]);

		if (Auth::attempt($credentials)) {
			$request->session()->regenerate();
			
			$user = Auth::user();
			
			// 🔴 FORÇAR REDIRECIONAMENTO (sem intended)
			if (in_array($user->role, ['admin', 'admin_master']) || $user->is_admin) {
				return redirect('/dashboard'); // Sistema interno admin
			}
			
			if ($user->role === 'client') {
				return redirect('/portal/dashboard'); // Novo portal cliente
			}
			
			// Fallback
			return redirect('/dashboard');
		}

		return back()->withErrors([
			'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
		])->onlyInput('email');
	}    

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}