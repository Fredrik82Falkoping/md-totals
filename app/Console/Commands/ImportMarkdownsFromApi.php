<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MarkdownImportService;
use Illuminate\Console\Command;

class ImportMarkdownsFromApi extends Command
{
    protected $signature = 'md:import-api {tenant_endpoint?} {store_code?}';
    protected $description = 'Hämtar och importerar nedsättningsdata från MD Totals API för en eller alla tenants';

    public function handle(MarkdownImportService $importService): void
    {
        $tenantEndpointArg = $this->argument('tenant_endpoint');
        $storeCodeArg = $this->argument('store_code');

        // If both arguments are provided: only import for the specific tenant (as before, good for manual testing)
        if ($tenantEndpointArg && $storeCodeArg) {
            $tenant = Tenant::firstOrCreate(
                ['store_code' => $storeCodeArg],
                ['name' => $storeCodeArg, 'api_endpoint' => $tenantEndpointArg]
            );

            $this->importTenant($tenant, $importService);
            return;
        }

        // Otherwise: loop over ALL tenants that have an api_endpoint configured
        $tenants = Tenant::whereNotNull('api_endpoint')->get();

        if ($tenants->isEmpty()) {
            $this->warn('Inga tenants med konfigurerat api_endpoint hittades.');
            return;
        }

        $this->info("Startar import för {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            $this->importTenant($tenant, $importService);
        }

        $this->info('Alla tenants klara.');
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