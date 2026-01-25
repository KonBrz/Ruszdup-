<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    // ===========================================
    // PANEL ACCESS
    // ===========================================

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'web')->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin');

        $response->assertStatus(200);
    }

    public function test_guest_can_view_admin_login_page(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_authenticated_admin_accessing_login_redirects_to_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/login');

        $response->assertRedirect('/admin');
    }

    // ===========================================
    // RESOURCE ACCESS - UserResource (slug: users)
    // ===========================================

    public function test_admin_can_access_users_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_users_create(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/users/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_users_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin, 'web')->get("/admin/users/{$user->id}/edit");

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_users_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'web')->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_users_create(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'web')->get('/admin/users/create');

        $response->assertStatus(403);
    }

    // ===========================================
    // RESOURCE ACCESS - FlaggedResource (slug: flagged)
    // ===========================================

    public function test_admin_can_access_flagged_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/flagged');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_flagged_create(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/flagged/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_flagged_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $flagged = \App\Models\Flagged::factory()->create();

        $response = $this->actingAs($admin, 'web')->get("/admin/flagged/{$flagged->id}/edit");

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_flagged_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'web')->get('/admin/flagged');

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_flagged_create(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'web')->get('/admin/flagged/create');

        $response->assertStatus(403);
    }

    // ===========================================
    // GUEST ACCESS TO RESOURCES
    // ===========================================

    public function test_guest_cannot_access_users_list(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/admin/login');
    }

    public function test_guest_cannot_access_flagged_list(): void
    {
        $response = $this->get('/admin/flagged');

        $response->assertRedirect('/admin/login');
    }
}
