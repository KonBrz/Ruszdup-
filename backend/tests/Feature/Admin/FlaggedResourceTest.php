<?php

namespace Tests\Feature\Admin;

use App\Enums\Decision;
use App\Filament\Resources\FlaggedResource\Pages\CreateFlagged;
use App\Filament\Resources\FlaggedResource\Pages\EditFlagged;
use App\Filament\Resources\FlaggedResource\Pages\ListFlagged;
use App\Models\Flagged;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FlaggedResourceTest extends TestCase
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

    public function test_list_page_displays_flags(): void
    {
        $flags = Flagged::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(ListFlagged::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($flags);
    }

    public function test_list_page_displays_flag_with_user_relation(): void
    {
        $user = User::factory()->create(['name' => 'Flagged User']);
        $flag = Flagged::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($this->admin)
            ->test(ListFlagged::class)
            ->assertCanSeeTableRecords([$flag]);
    }

    public function test_list_page_displays_flag_with_trip_relation(): void
    {
        $trip = Trip::factory()->create(['title' => 'Flagged Trip']);
        $flag = Flagged::factory()->create(['trip_id' => $trip->id]);

        Livewire::actingAs($this->admin)
            ->test(ListFlagged::class)
            ->assertCanSeeTableRecords([$flag]);
    }

    public function test_list_page_can_sort_by_id(): void
    {
        $flag1 = Flagged::factory()->create();
        $flag2 = Flagged::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListFlagged::class)
            ->sortTable('id')
            ->assertCanSeeTableRecords([$flag1, $flag2], inOrder: true);
    }

    // ===========================================
    // EDIT PAGE
    // ===========================================

    public function test_edit_page_loads_flag_data(): void
    {
        $flag = Flagged::factory()->create([
            'reason' => 'Test reason',
            'is_closed' => false,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->assertSuccessful();
    }

    public function test_can_update_flag_decision_to_up_to_standard(): void
    {
        $flag = Flagged::factory()->create([
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm([
                'decision' => Decision::UpToStandard->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(Decision::UpToStandard->value, $flag->fresh()->decision->value);
    }

    public function test_can_close_flag(): void
    {
        $flag = Flagged::factory()->create(['is_closed' => false]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['is_closed' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('flagged', [
            'id' => $flag->id,
            'is_closed' => true,
        ]);
    }

    public function test_can_reopen_flag(): void
    {
        $flag = Flagged::factory()->create(['is_closed' => true]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['is_closed' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('flagged', [
            'id' => $flag->id,
            'is_closed' => false,
        ]);
    }

    // ===========================================
    // BUSINESS LOGIC: Decision::Remove effects
    // ===========================================

    public function test_decision_remove_bans_flagged_user(): void
    {
        $user = User::factory()->create(['is_banned' => false]);
        $flag = Flagged::factory()->create([
            'user_id' => $user->id,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['decision' => Decision::Remove->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_banned' => true,
        ]);
    }

    public function test_decision_remove_deletes_flagged_trip(): void
    {
        $trip = Trip::factory()->create();
        $flag = Flagged::factory()->create([
            'trip_id' => $trip->id,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['decision' => Decision::Remove->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }

    public function test_decision_remove_deletes_flagged_task(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
        ]);
        $flag = Flagged::factory()->create([
            'task_id' => $task->id,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['decision' => Decision::Remove->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_decision_up_to_standard_does_not_ban_user(): void
    {
        $user = User::factory()->create(['is_banned' => false]);
        $flag = Flagged::factory()->create([
            'user_id' => $user->id,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['decision' => Decision::UpToStandard->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_banned' => false,
        ]);
    }

    public function test_decision_up_to_standard_does_not_delete_trip(): void
    {
        $trip = Trip::factory()->create();
        $flag = Flagged::factory()->create([
            'trip_id' => $trip->id,
            'decision' => Decision::None->value,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->fillForm(['decision' => Decision::UpToStandard->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('trips', ['id' => $trip->id]);
    }

    // ===========================================
    // DELETE
    // ===========================================

    public function test_can_delete_flag_from_edit_page(): void
    {
        $flag = Flagged::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditFlagged::class, ['record' => $flag->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('flagged', ['id' => $flag->id]);
    }

    public function test_can_bulk_delete_flags(): void
    {
        $flags = Flagged::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(ListFlagged::class)
            ->callTableBulkAction('delete', $flags);

        foreach ($flags as $flag) {
            $this->assertDatabaseMissing('flagged', ['id' => $flag->id]);
        }
    }
}
