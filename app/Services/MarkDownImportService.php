<?php

namespace App\Services;

use App\Models\Markdown;
use App\Models\Tenant;
use App\Enums\WriteOffReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MarkdownImportService
{
    public function __construct(
        private MdTotalsApiClient $apiClient,
    ) {}

    public function importForTenant(Tenant $tenant, string $tenantEndpoint): int
    {
        $lastSync = $tenant->last_synced_at ?? Carbon::createFromTimestamp(0);

        Log::info('Import startar (strömmande)', [
            'tenant' => $tenant->name,
            'last_synced_at' => $lastSync->toDateTimeString(),
        ]);

        $rows = [];
        $now = now();
        $latestRegisteredAt = $lastSync;
        $totalProcessed = 0;
        $totalInserted = 0;

        foreach ($this->apiClient->streamItemsLaterThan($tenantEndpoint, $lastSync) as $item) {
            $totalProcessed++;

            try {
                $value = $item;

                $registeredAt = Carbon::parse($value['dtRegistered']);
                if ($registeredAt->greaterThan($latestRegisteredAt)) {
                    $latestRegisteredAt = $registeredAt;
                }

                $currencyScale = 10 ** $value['usCurrencyDecimalPos'];
                $qtyScale = 10 ** $value['usQtyDecimalPos'];

                $reducedPrice = $value['ulMdPrice'] / $currencyScale;
                $purchasePrice = $value['dNominalCostPrice'];

                $marginAmount = $reducedPrice - $purchasePrice;
                $marginPercent = $purchasePrice > 0
                    ? round(($marginAmount / $purchasePrice) * 100, 2)
                    : 0;

                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'source_key' => $value['strKey'],
                    'product_id' => (string) $value['ulProductKey'],
                    'scanned_at' => $registeredAt,
                    'reason' => $value['enReason'],
                    'is_group' => $value['bIsGroup'],
                    'group_key' => $value['ulGroupKey'],
                    'variable_quantity' => $value['bPriceType'],
                    'unit_of_measure' => $value['enUOM'],
                    'packs' => $value['ulPacks'],
                    'quantity' => $value['ulQty'] / $qtyScale,
                    'regular_price' => $value['ulNormalPrice'] / $currencyScale,
                    'reduced_price' => $reducedPrice,
                    'discount_amount' => ($value['ulNormalPrice'] - $value['ulMdPrice']) / $currencyScale,
                    'discount_percent' => $value['ulNormalPrice'] > 0
                        ? round((($value['ulNormalPrice'] - $value['ulMdPrice']) / $value['ulNormalPrice']) * 100, 2)
                        : 0,
                    'markdown_value' => $value['ulMdVal'] / $currencyScale,
                    'purchase_price' => $purchasePrice,
                    'cost_price_by_portion' => $value['dCostPriceByPortion'],
                    'margin_amount' => $marginAmount,
                    'margin_percent' => $marginPercent,
                    'currency' => $value['strCurrency'],                    
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } catch (\Throwable $e) {
                Log::error('Import: fel vid tolkning av post', [
                    'index' => $totalProcessed,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (count($rows) >= 40) {
                try {
                    $totalInserted += Markdown::insertOrIgnore($rows);
                } catch (\Throwable $e) {
                    Log::error('Import: fel vid batch-insert', [
                        'batch_size' => count($rows),
                        'error' => $e->getMessage(),
                    ]);
                }
                $rows = [];

                if ($totalProcessed % 5000 === 0) {
                    Log::info("Import: {$totalProcessed} poster bearbetade hittills...");
                }
            }
        }

        // Sista, ofullständiga batchen
        if (!empty($rows)) {
            $totalInserted += Markdown::insertOrIgnore($rows);
        }

        Log::info('Import klar', [
            'total_processed' => $totalProcessed,
            'total_inserted' => $totalInserted,
            'new_last_synced_at' => $latestRegisteredAt->toDateTimeString(),
        ]);

        $tenant->update(['last_synced_at' => $latestRegisteredAt]);

        foreach (['week', 'month', 'year'] as $period) {
            Cache::forget("available_periods_{$period}_tenant_{$tenant->id}");
        }

        return $totalInserted;
    }
}