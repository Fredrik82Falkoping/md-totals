<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MdTotalsApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.mdtotals.base_url');
    }

    /**
     * Hämtar alla poster registrerade efter angiven tidpunkt för en given tenant.
     */
    public function fetchItemsLaterThan(string $tenantEndpoint, Carbon $after): array
    {
        $formattedAfter = $after->format('Y.m.d H:i:s.u');

        $url = "{$this->baseUrl}/{$tenantEndpoint}/MdTotals/ReadItemsLaterThan";

        $response = Http::withOptions(['verify' => false]) // se notering nedan om SSL
            ->timeout(30)
            ->get($url, ['after' => $formattedAfter]);

        if (!$response->successful()) {
            Log::error('MdTotals API-anrop misslyckades', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("API-anrop misslyckades: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }
}