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
        $url = "{$this->baseUrl}/{$tenantEndpoint}/MdTotals/ReadItemsLaterThan";

        Log::info('MdTotals API: skickar strömmande anrop', [
            'url' => $url,
            'after' => $formattedAfter,
        ]);

        $response = Http::withOptions([
            'verify' => false,
            'stream' => true, // viktigt: hämta som ström, inte hela body direkt
        ])
            ->timeout(120)
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
}