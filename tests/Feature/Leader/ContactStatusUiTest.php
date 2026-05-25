<?php

namespace Tests\Feature\Leader;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStatusUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_contacts_page_shows_explicit_save_status_button(): void
    {
        $team = Team::create(['name' => 'Tim UI Status']);

        $leader = User::factory()->create([
            'role' => User::ROLE_MAIN_MARKETING,
            'team_id' => $team->id,
            'main_marketing_id' => null,
        ]);

        $subLeader = User::factory()->create([
            'role' => User::ROLE_ASSISTANT_MARKETING,
            'team_id' => $team->id,
            'main_marketing_id' => null,
        ]);

        Contact::create([
            'phone' => '628333333333',
            'normalized_phone' => '628333333333',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $team->id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
            'status' => Contact::STATUS_UNCONTACTED,
        ]);

        $response = $this->actingAs($leader)
            ->get(route('leader.contacts.index'));

        $response->assertOk()
            ->assertSee('contact-status-select')
            ->assertSee('contact-status-save')
            ->assertSee('Save Status');
    }
}
