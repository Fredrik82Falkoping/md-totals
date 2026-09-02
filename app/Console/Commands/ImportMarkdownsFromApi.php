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

        // Om båda argumenten anges: kör bara den specifika tenanten (som innan, bra för manuell testning)
        if ($tenantEndpointArg && $storeCodeArg) {
            $tenant = Tenant::firstOrCreate(
                ['store_code' => $storeCodeArg],
                ['name' => $storeCodeArg, 'api_endpoint' => $tenantEndpointArg]
            );

            $this->importTenant($tenant, $importService);
            return;
        }

        // Annars: loopa över ALLA tenants som har ett api_endpoint konfigurerat
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
            // Fortsätter till nästa tenant, en trasig tenant stoppar inte hela körningen
        }
    }
}