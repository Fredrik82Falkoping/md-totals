<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JsonMachine\Items;
use GuzzleHttp\Psr7\StreamWrapper;

class MdTotalsApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.mdtotals.base_url');
    }

    /**
     * Returnerar en generator som ger ett item i taget,
     * utan att ladda hela JSON-svaret i minnet på en gång.
     */
    public function streamItemsLaterThan(string $tenantEndpoint, Carbon $after): iterable
    {
        $formattedAfter = $after->format('Y.m.d H:i:s.u');
        $url = "{$this->baseUrl}/" . rawurlencode($tenantEndpoint) . "/MdTotals/ReadItemsLaterThan";

        Log::info('MdTotals API: ', [
            'url' => $url,
            'after' => $formattedAfter,
        ]);

        $response = Http::withOptions([
            'verify' => false,
            'stream' => true, // viktigt: hämta som ström, inte hela body direkt
        ])
            ->timeout(300)
            ->get($url, ['after' => $formattedAfter]);

        if (!$response->successful()) {
            Log::error('MdTotals API: anrop misslyckades', [
                'status' => $response->status(),
            ]);
            throw new \RuntimeException("API-anrop misslyckades: HTTP {$response->status()}");
        }

        // Läs den underliggande PSR-7-strömmen och tolka JSON inkrementellt
        $stream = $response->toPsrResponse()->getBody();

        //$items = Items::fromStream(StreamWrapper::getResource($stream));
        $items = Items::fromStream(
            StreamWrapper::getResource($stream),
            ['decoder' => new \JsonMachine\JsonDecoder\ExtJsonDecoder(true)]
        );

        foreach ($items as $item) {
            yield $item;
        }
    }

    public function assertEndpointExists(string $tenantEndpoint): void
    {
        $url = "{$this->baseUrl}/" . rawurlencode($tenantEndpoint) . "/MdTotals/ReadItemsLaterThan";

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(300)
                ->get($url, ['after' => Carbon::createFromTimestamp(0)->format('Y.m.d H:i:s.u')]);
        } catch (\Throwable $e) {
            throw new \RuntimeException("API-endpointen '{$tenantEndpoint}' kunde inte verifieras.", 0, $e);
        }

        if ($response->status() === 404) {
            throw new \RuntimeException("Ingen kund hittades med API-endpointen '{$tenantEndpoint}'.");
        }

        if (!$response->successful()) {
            throw new \RuntimeException("API-endpointen '{$tenantEndpoint}' kunde inte verifieras (HTTP {$response->status()}).");
        }
    }
}