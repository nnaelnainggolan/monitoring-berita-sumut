<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';
    protected $description = 'Membuat admin awal tanpa menyimpan kata sandi bawaan di kode';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Nama admin'), 'email' => $this->ask('Email admin'),
            'password' => $this->secret('Kata sandi (minimal 12 karakter)'),
            'password_confirmation' => $this->secret('Ulangi kata sandi')];
        $validator = validator($data, ['name' => 'required|string|max:150', 'email' => 'required|email|unique:users|max:255',
            'password' => ['required', 'confirmed', Password::min(12)]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        User::create(collect($data)->except('password_confirmation')->all() + ['role' => 'admin', 'is_active' => true]);
        $this->info('Admin berhasil dibuat. Silakan login.');
        return self::SUCCESS;
    }
}
