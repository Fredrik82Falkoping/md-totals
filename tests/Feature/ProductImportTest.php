<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\MdTotalsApiClient;
use App\Services\MarkdownImportService;
use App\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_strings_are_parsed_and_upserted_by_store_and_article_key(): void
    {
        $tenant = Tenant::create([
            'name' => 'Kund A',
            'store_code' => 'STORE-A',
            'api_endpoint' => 'tenant-a',
        ]);
        Product::create([
            'store_key' => 'STORE-A',
            'article_key' => 123,
            'group_key' => 1,
            'name' => 'Gammalt namn',
        ]);

        $apiClient = Mockery::mock(MdTotalsApiClient::class);
        $apiClient->shouldReceive('streamArticleStrings')->once()
            ->with('tenant-a')
            ->andReturn([['serializedValue' => $this->serializedArticle(123, 42, 'Nytt namn')]]);

        $count = (new ProductImportService($apiClient))->importForTenant($tenant, 'tenant-a');

        $this->assertSame(1, $count);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'store_key' => 'STORE-A',
            'article_key' => 123,
            'group_key' => 42,
            'name' => 'Nytt namn',
        ]);
    }

    public function test_regular_tenant_import_also_imports_products(): void
    {
        $tenant = Tenant::create([
            'name' => 'Kund A',
            'store_code' => 'STORE-A',
            'api_endpoint' => 'tenant-a',
        ]);

        $apiClient = Mockery::mock(MdTotalsApiClient::class);
        $apiClient->shouldReceive('streamItemsLaterThan')->once()->andReturn([]);
        $apiClient->shouldReceive('streamArticleStrings')->once()
            ->with('tenant-a')
            ->andReturn([['serializedValue' => $this->serializedArticle(456, 43, 'Artikel')]]);

        $count = (new MarkdownImportService($apiClient, new ProductImportService($apiClient)))
            ->importForTenant($tenant, 'tenant-a');

        $this->assertSame(0, $count);
        $this->assertDatabaseHas('products', [
            'store_key' => 'STORE-A',
            'article_key' => 456,
            'group_key' => 43,
            'name' => 'Artikel',
        ]);
    }

    public function test_article_strings_are_fetched_from_the_new_endpoint(): void
    {
        Http::fake([
            '*ArticleStrings/ReadItems' => Http::response([
                ['serializedValue' => $this->serializedArticle(789, 3, 'Artikel')],
            ]),
        ]);

        $items = iterator_to_array((new MdTotalsApiClient())->streamArticleStrings('tenant-a'));

        $this->assertCount(1, $items);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/tenant-a/ArticleStrings/ReadItems'));
    }

    public function test_products_can_be_imported_without_running_the_markdown_import(): void
    {
        $tenant = Tenant::create([
            'name' => 'Kund A',
            'store_code' => 'STORE-A',
            'api_endpoint' => 'tenant-a',
        ]);
        $importService = Mockery::mock(ProductImportService::class);
        $importService->shouldReceive('importForTenant')->once()
            ->with(Mockery::on(fn (Tenant $importedTenant) => $importedTenant->is($tenant)), 'tenant-a')
            ->andReturn(9);
        $this->app->instance(ProductImportService::class, $importService);

        $this->artisan('md:import-products', ['tenant_endpoint' => 'tenant-a'])
            ->expectsOutputToContain('9 produkter importerade/uppdaterade')
            ->assertExitCode(0);
    }

    private function serializedArticle(int $articleKey, int $groupKey, string $name): string
    {
        $fields = array_fill(0, 37, '');
        $fields[2] = (string) $articleKey;
        $fields[5] = (string) $groupKey;
        $fields[36] = $name;

        return implode("\t", $fields);
    }
}
