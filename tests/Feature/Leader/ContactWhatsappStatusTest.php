<?php

namespace Tests\Feature\Leader;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactWhatsappStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_click_hubungi_updates_status_same_as_manual(): void
    {
        $team = Team::create(['name' => 'Tim WA']);

        $leader = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'team_id' => $team->id,
            'leader_id' => null,
        ]);

        $subLeader = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'team_id' => $team->id,
            'leader_id' => null,
        ]);

        $contact = Contact::create([
            'contact_name' => 'Test Contact',
            'phone' => '628123456789',
            'normalized_phone' => '628123456789',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $team->id,
            'sub_leader_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'leader_id' => null,
            'status' => Contact::STATUS_UNCONTACTED,
        ]);

        $response = $this->actingAs($leader)
            ->get(route('leader.contacts.whatsapp', $contact));

        $response->assertRedirect('https://wa.me/628123456789');

        $contact->refresh();
        $this->assertSame(Contact::STATUS_CONTACTED, $contact->status);
        $this->assertSame($leader->id, $contact->status_updated_by);
        $this->assertNotNull($contact->status_updated_at);
    }

    public function test_leader_cannot_access_contact_from_other_team(): void
    {
        $teamA = Team::create(['name' => 'Tim A']);
        $teamB = Team::create(['name' => 'Tim B']);

        $leaderA = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'team_id' => $teamA->id,
            'leader_id' => null,
        ]);

        $subLeaderB = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'team_id' => $teamB->id,
            'leader_id' => null,
        ]);

        $contact = Contact::create([
            'contact_name' => 'Other Contact',
            'phone' => '628123123123',
            'normalized_phone' => '628123123123',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $teamB->id,
            'sub_leader_id' => $subLeaderB->id,
            'input_by' => $subLeaderB->id,
            'leader_id' => null,
            'status' => Contact::STATUS_UNCONTACTED,
        ]);

        $response = $this->actingAs($leaderA)
            ->get(route('leader.contacts.whatsapp', $contact));

        $response->assertNotFound();

        $contact->refresh();
        $this->assertSame(Contact::STATUS_UNCONTACTED, $contact->status);
        $this->assertNull($contact->status_updated_by);
    }
}
