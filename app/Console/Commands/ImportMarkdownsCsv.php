<?php

namespace App\Console\Commands;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportMarkdownsCsv extends Command
{
    protected $signature = 'md:import-csv {path}';
    protected $description = 'Imports CSV export from the MD Totals C++ application. Tenant is derived from the filename.';

    public function handle(): void
    {
        $path = $this->argument('path');
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $tenantName = Str::before($filename, '_Markdowns');

        Log::info($filename);
        Log::info($tenantName);

        $tenant = Tenant::firstOrCreate(
            ['store_code' => $tenantName],
            ['name' => $tenantName]
        );

        $latestScannedAt = Markdown::where('tenant_id', $tenant->id)->max('scanned_at');
        $latestScannedAt = $latestScannedAt ? Carbon::parse($latestScannedAt) : null;

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, separator: ';');
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];
        $totalRows = 0;
        $now = now();

        while (($data = fgetcsv($handle, separator: ';')) !== false) {
            $totalRows++;
            $data = array_combine($headers, $data);
            $scannedAt = Carbon::parse($data['Date']);

            if ($latestScannedAt && $scannedAt->lessThanOrEqualTo($latestScannedAt)) {
                continue;
            }

            $rows[] = [
                'tenant_id' => $tenant->id,
                'product_id' => ltrim($data['ProductID'], "'"),
                'name' => $data['Name'] ?: null,
                'k_id' => $data['K-ID'] ?: null,
                'category' => $data['Kategori'] ?: null,
                'scanned_at' => $scannedAt,
                'quantity' => $this->parseDecimal($data['St.']),
                'weight_kg' => $this->parseDecimal($data['kg']),
                'regular_price' => $this->parseDecimal($data['Ordinarie Pris [Kr]']),
                'reduced_price' => $this->parseDecimal($data['Sänkt till [Kr]']),
                'discount_amount' => $this->parseDecimal($data['Nedsatt [Kr]']),
                'discount_percent' => $this->parseDecimal($data['Nedsatt [%]']),
                'purchase_price' => $this->parseDecimal($data['Inpris (Förp) [Kr]']),
                'margin_amount' => $this->parseDecimal($data['Marginal [Kr]']),
                'margin_percent' => $this->parseDecimal($data['Marginal [%]']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        $insertedCount = 0;

        foreach (array_chunk($rows, 500) as $chunk) {
            Markdown::insert($chunk);
            $insertedCount += count($chunk);
        }

        $skipped = $totalRows - $insertedCount;

        $this->info("Import complete for tenant \"{$tenant->name}\": {$insertedCount} inserted, {$skipped} already exists.");
    }

    private function parseDecimal(?string $value): ?float
    {
        $value = trim($value ?? '');
        if ($value === '') return null;
        return (float) str_replace(',', '.', $value);
    }
}