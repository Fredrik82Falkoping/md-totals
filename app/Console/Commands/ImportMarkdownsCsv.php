<?php 
// app/Console/Commands/ImportMarkdownsCsv.php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Markdown;
use Illuminate\Support\Str;

class ImportMarkdownsCsv extends Command
{
    protected $signature = 'md:import-csv {path}';
    protected $description = 'Imports CSV export from the MD Totals C++ application';

    public function handle(): void
    {
        $path = $this->argument('path');
        $filename = basename($path);
        $tenantName = Str::before($filename, '_Markdowns');
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, separator: ';');
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        while (($row = fgetcsv($handle, separator: ';')) !== false) {
            $data = array_combine($headers, $row);

            Log::info($data);

            Markdown::insertOrIgnore([
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
                'tenant_id' => $tenantName ?: null
            ]); 
        }

        fclose($handle);
        $this->info('Import complete.');
    }

    private function parseDecimal(?string $value): ?float
    {
        $value = trim($value ?? '');
        if ($value === '') return null;
        return (float) str_replace(',', '.', $value);
    }
}