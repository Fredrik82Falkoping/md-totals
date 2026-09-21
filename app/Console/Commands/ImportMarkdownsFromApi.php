<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MarkdownImportService;
use Illuminate\Console\Command;

class ImportMarkdownsFromApi extends Command
{
    protected $signature = 'md:import-api {tenant_endpoint?} {store_code?} {name?}';
    protected $description = 'Hämtar och importerar nedsättningsdata från MD Totals API för en eller alla tenants';

    public function handle(MarkdownImportService $importService): int
    {
        $tenantEndpointArg = $this->argument('tenant_endpoint');
        $storeCodeArg = $this->argument('store_code');
        $nameArg = $this->argument('name');

        if ($tenantEndpointArg || $storeCodeArg || $nameArg) {
            if (!$tenantEndpointArg || !$storeCodeArg) {
                $this->error('För en tenant-import krävs både tenant_endpoint och store_code.');
                return self::FAILURE;
            }

            try {
                $result = $importService->importNewTenant($tenantEndpointArg, $storeCodeArg, $nameArg);
                $this->info("Kund '{$result['tenant']->name}' importerad. {$result['count']} nya rader.");
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        // Otherwise: loop over ALL tenants that have an api_endpoint configured
        $tenants = Tenant::whereNotNull('api_endpoint')->get();

        if ($tenants->isEmpty()) {
            $this->warn('Inga tenants med konfigurerat api_endpoint hittades.');
            return self::SUCCESS;
        }

        $this->info("Startar import för {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            $this->importTenant($tenant, $importService);
        }

        $this->info('Alla tenants klara.');

        return self::SUCCESS;
    }

    private function importTenant(Tenant $tenant, MarkdownImportService $importService): void
    {
        $this->info("→ {$tenant->name} ({$tenant->api_endpoint})...");

        try {
            $count = $importService->importForTenant($tenant, $tenant->api_endpoint);
            $this->info("  Klart. {$count} nya rader.");
        } catch (\Throwable $e) {
            $this->error("  Misslyckades: {$e->getMessage()}");
            // Continues to the next tenant, a broken tenant does not stop the entire execution
        }
    }
}