<?php

namespace Tests\Feature\SubLeader;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_leader_can_store_contacts_from_textbox_with_space_separator(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $subLeader = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => $leader->id,
        ]);

        Contact::create([
            'contact_name' => 'Existing',
            'phone' => '628111111001',
            'sub_leader_id' => $subLeader->id,
            'leader_id' => $leader->id,
        ]);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'contact_name' => 'Batch Input',
                'phones' => "628111111001 081234567890 abc, 628999888777",
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', [
            'phone' => '081234567890',
            'contact_name' => 'Batch Input',
            'sub_leader_id' => $subLeader->id,
            'leader_id' => $leader->id,
        ]);

        $this->assertDatabaseHas('contacts', [
            'phone' => '628999888777',
            'contact_name' => 'Batch Input',
        ]);

        $this->assertEquals(3, Contact::count());
    }
}
