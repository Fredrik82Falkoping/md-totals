<?php

namespace App\Console\Commands;

use App\Models\Markdown;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, separator: ';');
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];
        $now = now();

        while (($data = fgetcsv($handle, separator: ';')) !== false) {
            $data = array_combine($headers, $data);

            $rows[] = [
                'tenant_id' => $tenant->id,
                'product_id' => ltrim($data['ProductID'], "'"),
                'name' => $data['Name'] ?: null,
                'k_id' => $data['K-ID'] ?: null,
                'category' => $data['Kategori'] ?: null,
                'scanned_at' => $data['Date'],
                'month' => $data['Manad'] ?: null,
                'week' => $data['Vecka'] ?: null,
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

        $totalRows = count($rows);
        $insertedCount = 0;

        foreach (array_chunk($rows, 500) as $chunk) {
            $insertedCount += Markdown::insertOrIgnore($chunk);
        }

        $skipped = $totalRows - $insertedCount;

        $this->info("Import complete for tenant \"{$tenant->name}\": {$insertedCount} inserted, {$skipped} skipped as duplicates.");
    }

    private function parseDecimal(?string $value): ?float
    {
        $value = trim($value ?? '');
        if ($value === '') return null;
        return (float) str_replace(',', '.', $value);
    }
}