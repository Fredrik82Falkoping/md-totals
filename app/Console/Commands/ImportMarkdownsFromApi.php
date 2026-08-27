<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\MarkdownImportService;
use Illuminate\Console\Command;

class ImportMarkdownsFromApi extends Command
{
    protected $signature = 'md:import-api {tenant_endpoint} {store_code}';
    protected $description = 'Hämtar och importerar nedsättningsdata från MD Totals API för en tenant';

    public function handle(MarkdownImportService $importService): void
    {
        $storeCode = $this->argument('store_code');
        $tenantEndpoint = $this->argument('tenant_endpoint');

        $tenant = Tenant::firstOrCreate(
            ['store_code' => $storeCode],
            ['name' => $tenantEndpoint]
        );

        $this->info("Hämtar data för tenant \"{$tenant->name}\" via endpoint \"{$tenantEndpoint}\"...");

        try {
            $count = $importService->importForTenant($tenant, $tenantEndpoint);
            $this->info("Klart. {$count} nya rader importerade.");
        } catch (\Throwable $e) {
            $this->error("Import misslyckades: {$e->getMessage()}");
        }
    }
}