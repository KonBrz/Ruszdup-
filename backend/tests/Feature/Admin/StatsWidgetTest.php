<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\StatsOverview;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_stats_widget_renders_successfully(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StatsOverview::class)
            ->assertSuccessful();
    }

    public function test_stats_widget_renders_with_users(): void
    {
        User::factory()->count(5)->create();

        Livewire::actingAs($this->admin)
            ->test(StatsOverview::class)
            ->assertSuccessful();
    }

    public function test_stats_widget_renders_with_trips(): void
    {
        Trip::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(StatsOverview::class)
            ->assertSuccessful();
    }

    public function test_stats_widget_renders_with_tasks(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create();
        
        Task::factory()->count(4)->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(StatsOverview::class)
            ->assertSuccessful();
    }

    public function test_dashboard_loads_with_widget(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin');

        $response->assertStatus(200);
    }
}
