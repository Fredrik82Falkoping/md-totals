<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create
        {name : Användarnamn}
        {tenant_code? : Butikens store_code, utelämnas för admin}
        {--admin : Skapa en adminanvändare}
        {--password= : Lösenord för admin, annars används butikens store_code}';

    protected $description = 'Skapar en användare kopplad till en butik eller en adminanvändare';

    public function handle(): int
    {
        $name = $this->argument('name');
        $tenantCode = $this->argument('tenant_code');
        $isAdmin = (bool) $this->option('admin');

        if (User::where('name', $name)->exists()) {
            $this->error("Användarnamnet '{$name}' används redan.");

            return self::FAILURE;
        }

        if ($isAdmin) {
            if ($tenantCode) {
                $this->error('En admin ska inte ha en butik kopplad.');

                return self::FAILURE;
            }

            $password = $this->option('password') ?: $this->secret('Lösenord');
            if (!$password) {
                $this->error('Ett lösenord krävs för admin.');

                return self::FAILURE;
            }

            $tenant = null;
        } else {
            if (!$tenantCode) {
                $this->error('Ange butikens store_code för en vanlig användare.');

                return self::FAILURE;
            }

            $tenant = Tenant::where('store_code', $tenantCode)->first();
            if (!$tenant) {
                $this->error("Ingen butik med store_code '{$tenantCode}' hittades.");

                return self::FAILURE;
            }

            $password = $tenant->store_code;
        }

        User::create([
            'name' => $name,
            'email' => null,
            'password' => Hash::make($password),
            'tenant_id' => $tenant?->id,
            'is_admin' => $isAdmin,
        ]);

        $this->info("Användaren '{$name}' skapades.");
        if (!$isAdmin) {
            $this->line("Butik: {$tenant->name} ({$tenant->store_code})");
            $this->line('Första lösenordet är butikens store_code.');
        }

        return self::SUCCESS;
    }
}
