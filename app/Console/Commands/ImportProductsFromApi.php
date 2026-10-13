<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ProductImportService;
use Illuminate\Console\Command;

class ImportProductsFromApi extends Command
{
    protected $signature = 'md:import-products {tenant_endpoint?}';
    protected $description = 'Hämtar och uppdaterar enbart produktdata från MD Totals API';

    public function handle(ProductImportService $importService): int
    {
        $tenantEndpoint = $this->argument('tenant_endpoint');
        $tenants = Tenant::query()
            ->whereNotNull('api_endpoint')
            ->when($tenantEndpoint, fn ($query) => $query->where('api_endpoint', $tenantEndpoint))
            ->orderBy('name')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error($tenantEndpoint
                ? "Ingen inläst tenant hittades med API-endpointen '{$tenantEndpoint}'."
                : 'Inga tenants med konfigurerat api_endpoint hittades.');

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($tenants as $tenant) {
            $this->info("→ {$tenant->name} ({$tenant->api_endpoint})...");

            try {
                $count = $importService->importForTenant($tenant, $tenant->api_endpoint);
                $this->info("  Produktimport klar. {$count} produkter importerade/uppdaterade.");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  Produktimporten misslyckades: {$e->getMessage()}");
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
