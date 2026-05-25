<?php

namespace Tests\Feature\Leader;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_update_contact_status_via_patch(): void
    {
        $team = Team::create(['name' => 'Tim Status']);

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

        $contact = Contact::create([
            'phone' => '628111111111',
            'normalized_phone' => '628111111111',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $team->id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
            'status' => Contact::STATUS_UNCONTACTED,
        ]);

        $response = $this->actingAs($leader)
            ->patchJson(route('leader.contacts.status', $contact), [
                'status' => 'contacted',
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'contacted',
                'label' => 'Sudah Dihubungi',
            ]);

        $contact->refresh();
        $this->assertSame(Contact::STATUS_CONTACTED, $contact->status);
        $this->assertSame($leader->id, $contact->status_updated_by);
        $this->assertNotNull($contact->status_updated_at);
    }

    public function test_leader_can_reset_status_to_uncontacted(): void
    {
        $team = Team::create(['name' => 'Tim Reset']);

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

        $contact = Contact::create([
            'phone' => '628222222222',
            'normalized_phone' => '628222222222',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $team->id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'status' => Contact::STATUS_CONTACTED,
            'status_updated_by' => $leader->id,
            'status_updated_at' => now(),
        ]);

        $response = $this->actingAs($leader)
            ->patchJson(route('leader.contacts.status', $contact), [
                'status' => 'uncontacted',
            ]);

        $response->assertOk();

        $contact->refresh();
        $this->assertSame(Contact::STATUS_UNCONTACTED, $contact->status);
        $this->assertSame($leader->id, $contact->status_updated_by);
        $this->assertNotNull($contact->status_updated_at);
    }
}
