<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // ===========================================
    // LIST PAGE
    // ===========================================

    public function test_list_page_displays_users(): void
    {
        $users = User::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($users);
    }

    public function test_list_page_can_search_users_by_name(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    public function test_list_page_can_sort_users_by_name(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userZ = User::factory()->create(['name' => 'Zack']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$userA, $userZ], inOrder: true);
    }

    // ===========================================
    // CREATE PAGE - Note: form doesn't have password field,
    // so we test validation only, not full creation
    // ===========================================

    public function test_create_page_renders(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->assertSuccessful();
    }

    public function test_create_user_validates_required_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => 'test@example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_user_validates_required_email(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'existing@example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    // ===========================================
    // EDIT PAGE
    // ===========================================

    public function test_edit_page_loads_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->assertSuccessful()
            ->assertFormSet([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);
    }

    public function test_can_edit_user_name(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Updated Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_toggle_user_admin_status(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm(['is_admin' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_can_ban_user(): void
    {
        $user = User::factory()->create(['is_banned' => false]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm(['is_banned' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_banned' => true,
        ]);
    }

    public function test_edit_user_validates_unique_email_ignores_self(): void
    {
        $user = User::factory()->create(['email' => 'myemail@example.com']);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->fillForm(['email' => 'myemail@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ===========================================
    // DELETE
    // ===========================================

    public function test_can_delete_user_from_edit_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $user->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_can_bulk_delete_users(): void
    {
        $users = User::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableBulkAction('delete', $users);

        foreach ($users as $user) {
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }
    }
}
