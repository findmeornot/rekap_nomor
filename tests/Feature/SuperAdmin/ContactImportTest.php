<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContactImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_import_contacts_into_selected_team(): void
    {
        $team = Team::create(['name' => 'Tim Superadmin']);
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        Contact::create([
            'contact_name' => 'Existing',
            'phone' => '628111111001',
            'normalized_phone' => '628111111001',
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $team->id,
            'sub_leader_id' => null,
            'input_by' => $superAdmin->id,
            'leader_id' => null,
        ]);

        $csv = implode("\n", [
            'name,phone',
            'Budi,628111111002',
            'Siti,628111111001',
            'Invalid,ABCDEF',
            'Rani,628111111003',
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $response = $this->actingAs($superAdmin)
            ->post(route('superadmin.import.store'), [
                'team_id' => $team->id,
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $contacts = Contact::query()->where('team_id', $team->id)->get();

        $this->assertCount(3, $contacts);
        $this->assertSame($team->id, $contacts->first()->team_id);
        $this->assertSame($superAdmin->id, $contacts->first()->input_by);
        $this->assertSame('628111111002', $contacts->where('normalized_phone', '628111111002')->first()->normalized_phone);
    }

    public function test_superadmin_import_requires_team_selection(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', "name,phone\nBudi,628111111002");

        $response = $this->actingAs($superAdmin)
            ->post(route('superadmin.import.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('team_id');
        $this->assertSame(0, Contact::count());
    }
}
