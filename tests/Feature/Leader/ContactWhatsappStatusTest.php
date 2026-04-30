<?php

namespace Tests\Feature\Leader;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactWhatsappStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_click_hubungi_marks_contact_as_contacted(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $subLeader = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => $leader->id,
        ]);

        $contact = Contact::create([
            'contact_name' => 'Test Contact',
            'phone' => '08123456789',
            'sub_leader_id' => $subLeader->id,
            'leader_id' => $leader->id,
        ]);

        $response = $this->actingAs($leader)
            ->get(route('leader.contacts.whatsapp', $contact));

        $response->assertRedirect('https://wa.me/628123456789');

        $contact->refresh();
        $this->assertNotNull($contact->contacted_at);
        $this->assertSame($leader->id, $contact->contacted_by_leader_id);
    }

    public function test_leader_cannot_mark_other_leader_contact(): void
    {
        $leaderA = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $leaderB = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $subLeaderB = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => $leaderB->id,
        ]);

        $contact = Contact::create([
            'contact_name' => 'Other Contact',
            'phone' => '628123123123',
            'sub_leader_id' => $subLeaderB->id,
            'leader_id' => $leaderB->id,
        ]);

        $response = $this->actingAs($leaderA)
            ->get(route('leader.contacts.whatsapp', $contact));

        $response->assertNotFound();

        $contact->refresh();
        $this->assertNull($contact->contacted_at);
        $this->assertNull($contact->contacted_by_leader_id);
    }
}
