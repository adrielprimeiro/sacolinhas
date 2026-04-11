<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-API-Key') !== env('SHEETS_API_KEY')) {
            return response()->json(['error' => 'Chave API inválida'], 401);
        }
        return $next($request);
    }
}
