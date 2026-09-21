<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_import_logs(): void
    {
        $tenant = Tenant::create(['name' => 'Kund A', 'store_code' => 'A']);
        $admin = User::factory()->create(['is_admin' => true, 'tenant_id' => null]);
        Log::create([
            'tenant_id' => $tenant->id,
            'imported_count' => 75,
            'status' => 'success',
            'started_at' => Carbon::parse('2026-09-21 03:37:00'),
        ]);

        $this->actingAs($admin)
            ->get(route('import-logs.index'))
            ->assertOk()
            ->assertSee('Kund A')
            ->assertSee('75');
    }

    public function test_regular_user_cannot_view_import_logs(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('import-logs.index'))
            ->assertForbidden();
    }

    public function test_import_logs_can_be_filtered_by_customer_and_time_interval(): void
    {
        $firstTenant = Tenant::create(['name' => 'Kund A', 'store_code' => 'A']);
        $secondTenant = Tenant::create(['name' => 'Kund B', 'store_code' => 'B']);
        $admin = User::factory()->create(['is_admin' => true, 'tenant_id' => null]);

        foreach ([
            [$firstTenant, '2026-09-21 03:37:00', 75],
            [$secondTenant, '2026-09-21 04:37:00', 12],
        ] as [$tenant, $startedAt, $count]) {
            Log::create([
                'tenant_id' => $tenant->id,
                'imported_count' => $count,
                'status' => 'success',
                'started_at' => Carbon::parse($startedAt),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('import-logs.index', [
                'tenant' => 'Kund A',
                'from' => '2026-09-21T03:00',
                'to' => '2026-09-21T04:00',
            ]))
            ->assertOk()
            ->assertSee('Kund A')
            ->assertDontSee('Kund B')
            ->assertSee('75')
            ->assertDontSee('12');
    }
}