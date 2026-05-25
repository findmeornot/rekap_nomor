<?php

namespace Tests\Feature\SubLeader;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    private function createTeamUsers(): array
    {
        $team = Team::create(['name' => 'Tim Test']);

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

        return [$team, $leader, $subLeader];
    }

    public function test_sub_leader_can_store_contacts_from_textbox_with_space_separator(): void
    {
        [, , $subLeader] = $this->createTeamUsers();

        Contact::create([
            'contact_name' => 'Existing',
            'phone' => '628111111001',
            'normalized_phone' => '628111111001',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
        ]);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'contact_name' => 'Batch Input',
                'phones' => "628111111001 081234567890 abc, 628999888777",
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', [
            'phone' => '6281234567890',
            'normalized_phone' => '6281234567890',
            'contact_name' => 'Batch Input',
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
            'period_key' => Contact::activePeriodKey(),
        ]);

        $this->assertEquals(3, Contact::count());
    }

    public function test_same_number_can_be_added_in_different_period(): void
    {
        [, , $subLeader] = $this->createTeamUsers();

        Contact::create([
            'phone' => '628123456789',
            'normalized_phone' => '628123456789',
            'period_key' => now()->subMonth()->format('Y-m'),
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
        ]);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'phones' => '628123456789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(2, Contact::count());
    }

    public function test_duplicate_detected_across_phone_formats_in_same_period(): void
    {
        [, , $subLeader] = $this->createTeamUsers();

        Contact::create([
            'phone' => '628123456789',
            'normalized_phone' => '628123456789',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
        ]);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'phones' => '08123456789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(1, Contact::count());
    }

    public function test_store_fails_without_team(): void
    {
        $subLeader = User::factory()->create([
            'role' => User::ROLE_ASSISTANT_MARKETING,
            'team_id' => null,
            'main_marketing_id' => null,
        ]);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'phones' => '628111111999',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('phones');
        $this->assertEquals(0, Contact::count());
    }

    public function test_sub_leader_can_store_contact_when_target_is_already_reached(): void
    {
        [, , $subLeader] = $this->createTeamUsers();

        for ($index = 1; $index <= User::TARGET_ASSISTANT_MARKETING; $index++) {
            Contact::create([
                'phone' => '628111111'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'normalized_phone' => '628111111'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'period_key' => Contact::activePeriodKey(),
                'team_id' => $subLeader->team_id,
                'assistant_marketing_id' => $subLeader->id,
                'input_by' => $subLeader->id,
                'main_marketing_id' => null,
            ]);
        }

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.store'), [
                'phones' => '628222222222',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contacts', [
            'phone' => '628222222222',
            'normalized_phone' => '628222222222',
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'period_key' => Contact::activePeriodKey(),
        ]);
        $this->assertEquals(User::TARGET_ASSISTANT_MARKETING + 1, Contact::count());
    }
}
