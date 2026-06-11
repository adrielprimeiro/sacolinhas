<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BancoInterPixService;

class RegisterInterWebhook extends Command
{
    protected $signature = 'inter:register-webhook {url? : A URL base do servidor (ex: https://meusite.com)}';
    protected $description = 'Registra o webhook de recebimento de Pix no Banco Inter';

    public function handle(BancoInterPixService $service)
    {
        $baseUrl = $this->argument('url') ?: config('app.url');

        if (empty($baseUrl)) {
            $this->error('A URL base do aplicativo (APP_URL) ou o argumento de URL não está configurado.');
            return 1;
        }

        $webhookUrl = rtrim($baseUrl, '/') . '/api/payment/webhook/inter';
        $this->info("Registrando webhook: {$webhookUrl}");

        try {
            $service->registrarWebhook($webhookUrl);
            $this->info('Webhook registrado com sucesso no Banco Inter!');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Erro ao registrar webhook: ' . $e->getMessage());
            return 1;
        }
    }
}
