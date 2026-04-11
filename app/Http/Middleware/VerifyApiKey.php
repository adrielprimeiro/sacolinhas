<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key'); // Ou outro cabeçalho de sua escolha

        if (!$apiKey || $apiKey !== env('API_KEY_IMPORT')) {
            return response()->json(['message' => 'Unauthorized - Invalid API Key'], 401);
        }

        return $next($request);
    }
	
	
	protected $except = [
    'twilio-out',
    'admin/live/*/send-whatsapp',
];
}
