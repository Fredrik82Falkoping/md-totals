<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MarkdownImportService;
use Illuminate\Console\Command;

class ImportMarkdownsFromApi extends Command
{
    protected $signature = 'md:import-api {tenant_endpoint} {store_code} {name?}';
    protected $description = 'Hämtar och importerar nedsättningsdata från MD Totals API för en tenant';

    public function handle(MarkdownImportService $importService): void
    {
        $storeCode = $this->argument('store_code');
        $tenantEndpoint = $this->argument('tenant_endpoint');
        $name = $this->argument('name') ?? $storeCode;

        $existingTenant = Tenant::where('store_code', $storeCode)->first();

        // Om tenanten redan finns sedan innan, uppdatera bara ev. saknad info och kör som vanligt
        if ($existingTenant) {
            if (!$existingTenant->api_endpoint) {
                $existingTenant->update(['api_endpoint' => $tenantEndpoint]);
            }

            $this->runImport($existingTenant, $tenantEndpoint, $importService);
            return;
        }

        // Ny tenant: testa API-anropet FÖRST, skapa bara raden om det lyckas
        $this->info("Ny tenant, testar anslutning mot \"{$tenantEndpoint}\" innan den sparas...");

        $tempTenant = new Tenant([
            'store_code' => $storeCode,
            'name' => $name,
            'api_endpoint' => $tenantEndpoint,
        ]);

        try {
            // Körs mot ett osparat Tenant-objekt - importForTenant behöver bara tenant_id vid batch-insert,
            // så vi sparar tenanten precis innan själva importlogiken, men efter att vi vet att API:et svarar
            $tempTenant->save();
            $count = $importService->importForTenant($tempTenant, $tenantEndpoint);
            $this->info("Klart. Ny tenant \"{$tempTenant->name}\" skapad, {$count} rader importerade.");
        } catch (\Throwable $e) {
            $this->error("Import misslyckades: {$e->getMessage()}");

            // API-anropet failade - ta bort tenanten igen, den ska inte finnas kvar
            if ($tempTenant->exists) {
                $tempTenant->delete();
                $this->warn("Tenant \"{$storeCode}\" togs bort igen eftersom importen misslyckades.");
            }
        }
    }

    private function runImport(Tenant $tenant, string $tenantEndpoint, MarkdownImportService $importService): void
    {
        $this->info("Hämtar data för tenant \"{$tenant->name}\" via endpoint \"{$tenantEndpoint}\"...");

        try {
            $count = $importService->importForTenant($tenant, $tenantEndpoint);
            $this->info("Klart. {$count} nya rader.");
        } catch (\Throwable $e) {
            $this->error("Import misslyckades: {$e->getMessage()}");
            // Tenanten fanns redan innan (verifierad tidigare), så vi tar INTE bort den
            // bara för att ett enstaka anrop misslyckades (t.ex. tillfälligt nätverksfel)
        }
    }
}