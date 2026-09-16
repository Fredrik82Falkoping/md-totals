<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_logged_in_and_sent_to_assigned_store(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $user = User::factory()->create([
            'name' => 'butik-a',
            'password' => Hash::make($tenant->store_code),
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->post('/login', [
            'username' => 'butik-a',
            'password' => 'A',
        ]);

        $response->assertRedirect('/statistics');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($tenant->id, session('tenant_id'));
    }

    public function test_admin_can_choose_a_store(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $admin = User::factory()->create(['name' => 'admin', 'is_admin' => true, 'tenant_id' => null]);

        $this->actingAs($admin)->get('/')->assertOk();

        $response = $this->post('/select-tenant', ['tenant_id' => $tenant->id]);

        $response->assertRedirect('/statistics');
        $this->assertSame($tenant->id, session('tenant_id'));
    }

    public function test_user_cannot_choose_another_store(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post('/select-tenant', ['tenant_id' => $tenant->id])
            ->assertForbidden();
    }

    public function test_admin_can_edit_store_name_and_password(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make('old-password'),
        ]);
        $admin = User::factory()->create(['is_admin' => true, 'tenant_id' => null]);

        $response = $this->actingAs($admin)->put(route('tenants.update', $tenant), [
            'name' => 'Butik B',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('tenants.select'));
        $this->assertSame('Butik B', $tenant->fresh()->name);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_blank_password_only_updates_store_name(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $oldPassword = Hash::make('old-password');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => $oldPassword,
        ]);
        $admin = User::factory()->create(['is_admin' => true, 'tenant_id' => null]);

        $this->actingAs($admin)->put(route('tenants.update', $tenant), [
            'name' => 'Butik B',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('tenants.select'));

        $this->assertSame('Butik B', $tenant->fresh()->name);
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_regular_user_cannot_edit_a_store(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->get(route('tenants.edit', $tenant))->assertForbidden();
        $this->actingAs($user)->put(route('tenants.update', $tenant), [
            'name' => 'Butik B',
        ])->assertForbidden();
    }

    public function test_user_can_log_out(): void
    {
        $tenant = Tenant::create(['name' => 'Butik A', 'store_code' => 'A']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}