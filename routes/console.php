<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:teacher', function (): int {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Demo accounts can only be created in a local environment.');

        return 1;
    }

    $email = 'professor@example.test';
    $password = 'password';

    User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => 'Professor de Demonstração',
            'password' => $password,
            'email_verified_at' => now(),
        ],
    );

    $this->info('Conta de demonstração pronta.');
    $this->line("E-mail: {$email}");
    $this->line("Senha: {$password}");
    $this->warn('Conta de demonstração local.');

    return 0;
})->purpose('Create or reset a local demonstration teacher account');
