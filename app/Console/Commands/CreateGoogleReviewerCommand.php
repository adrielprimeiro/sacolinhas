<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateGoogleReviewerCommand extends Command
{
    protected $signature = 'app:create-google-reviewer';
    protected $description = 'Cria ou atualiza a conta de demonstração para a auditoria do Google / YouTube API';

    public function handle()
    {
        $user = User::updateOrCreate(
            ['email' => 'google.reviewer@minhamania.net'],
            [
                'name' => 'Google Reviewer Demo',
                'password' => Hash::make('GoogleReview2026!'),
                'role' => 'admin',
                'bloqueado' => false,
            ]
        );

        $this->info("Conta criada/atualizada com sucesso: ID {$user->id}, Email: {$user->email}");
        return Command::SUCCESS;
    }
}
