<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Log as ImportLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProductImportService
{
    public function __construct(
        private MdTotalsApiClient $apiClient,
    ) {}

    public function importForTenant(Tenant $tenant, string $tenantEndpoint): int
    {
        $startedAt = now();

        try {
            $upserted = $this->performImportForTenant($tenant, $tenantEndpoint);
            $this->writeImportLog($tenant, $startedAt, $upserted, 'success');

            return $upserted;
        } catch (\Throwable $e) {
            $this->writeImportLog($tenant, $startedAt, 0, 'failed', $e->getMessage());

            throw $e;
        }
    }

    private function performImportForTenant(Tenant $tenant, string $tenantEndpoint): int
    {
        $rows = [];
        $now = now();
        $processed = 0;
        $upserted = 0;

        foreach ($this->apiClient->streamArticleStrings($tenantEndpoint) as $item) {
            $processed++;
            $serialized = $item['serializedValue'] ?? null;

            if (!is_string($serialized)) {
                Log::warning('ArticleStrings: saknar serializedValue', [
                    'tenant_id' => $tenant->id,
                    'index' => $processed,
                ]);
                continue;
            }

            $fields = explode("\t", $serialized);
            if ($processed === 1) {
                Log::info('ArticleStrings: första serialiserade strängens fält per position', [
                    'tenant_id' => $tenant->id,
                    'fields' => array_combine(
                        array_map(static fn (int $position): string => "field_{$position}", array_keys($fields)),
                        array_values($fields),
                    ),
                ]);
            }

            if (count($fields) <= 36) {
                Log::warning('ArticleStrings: felaktigt serialiserat värde', [
                    'tenant_id' => $tenant->id,
                    'index' => $processed,
                    'field_count' => count($fields),
                ]);
                continue;
            }

            $rows[] = [
                'tenant_id' => $tenant->id,
                'product_id' => (int) $fields[3],
                'group_key' => (int) $fields[5],
                'name' => $fields[36],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                $upserted += $this->upsert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $upserted += $this->upsert($rows);
        }

        Log::info('ArticleStrings-import klar', [
            'tenant_id' => $tenant->id,
            'processed' => $processed,
            'upserted' => $upserted,
        ]);

        return $upserted;
    }

    private function writeImportLog(
        Tenant $tenant,
        Carbon $startedAt,
        int $importedCount,
        string $status,
        ?string $error = null,
    ): void {
        try {
            ImportLog::create([
                'tenant_id' => $tenant->id,
                'import_type' => 'product',
                'imported_count' => $importedCount,
                'status' => $status,
                'error' => $error,
                'started_at' => $startedAt,
            ]);
        } catch (\Throwable $logException) {
            Log::error('Importlogg kunde inte sparas', [
                'tenant_id' => $tenant->id,
                'import_type' => 'product',
                'error' => $logException->getMessage(),
            ]);
        }
    }

    private function upsert(array $rows): int
    {
        return Product::upsert(
            $rows,
            ['tenant_id', 'product_id'],
            ['group_key', 'name'],
        );
    }
}
