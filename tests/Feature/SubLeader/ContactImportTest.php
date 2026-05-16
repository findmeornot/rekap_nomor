<?php

namespace Tests\Feature\SubLeader;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_leader_can_import_contacts_from_csv(): void
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
            'assistant_marketing_id' => $subLeader->id,
            'main_marketing_id' => $leader->id,
        ]);

        $csv = implode("\n", [
            'name,phone',
            'Budi,628111111002',
            'Siti,628111111001',
            'Invalid,ABCDEF',
            'Rani,628111111003',
            'DupInFile,628111111003',
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', [
            'contact_name' => 'Budi',
            'phone' => '628111111002',
            'assistant_marketing_id' => $subLeader->id,
            'main_marketing_id' => $leader->id,
        ]);

        $this->assertDatabaseHas('contacts', [
            'contact_name' => 'Rani',
            'phone' => '628111111003',
        ]);

        $this->assertEquals(3, Contact::count());
    }

    public function test_import_fails_for_sub_leader_without_leader_assignment(): void
    {
        $subLeader = User::factory()->create([
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => null,
        ]);

        $csv = implode("\n", [
            'name,phone',
            'Budi,628111111999',
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($subLeader)
            ->post(route('subleader.contacts.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file');
        $this->assertEquals(0, Contact::count());
    }
}
