<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleHierarchySeeder extends Seeder
{
    /**
     * Seed the application's role hierarchy and sample contacts.
     */
    public function run(): void
    {
        $superAdmin = User::updateOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => 'password123',
            'role' => User::ROLE_SUPERADMIN,
            'leader_id' => null,
        ]);

        $leaderA = User::updateOrCreate([
            'email' => 'leader1@example.com',
        ], [
            'name' => 'Leader Satu',
            'password' => 'password123',
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $leaderB = User::updateOrCreate([
            'email' => 'leader2@example.com',
        ], [
            'name' => 'Leader Dua',
            'password' => 'password123',
            'role' => User::ROLE_LEADER,
            'leader_id' => null,
        ]);

        $subLeaderA = User::updateOrCreate([
            'email' => 'subleader1@example.com',
        ], [
            'name' => 'Sub Leader Satu',
            'password' => 'password123',
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => $leaderA->id,
        ]);

        $subLeaderB = User::updateOrCreate([
            'email' => 'subleader2@example.com',
        ], [
            'name' => 'Sub Leader Dua',
            'password' => 'password123',
            'role' => User::ROLE_SUB_LEADER,
            'leader_id' => $leaderB->id,
        ]);

        $contacts = [
            [
                'contact_name' => 'Andi Pratama',
                'phone' => '628111111001',
                'sub_leader_id' => $subLeaderA->id,
                'leader_id' => $leaderA->id,
            ],
            [
                'contact_name' => 'Sari Dewi',
                'phone' => '628111111002',
                'sub_leader_id' => $subLeaderA->id,
                'leader_id' => $leaderA->id,
            ],
            [
                'contact_name' => 'Rian Nugroho',
                'phone' => '628111111003',
                'sub_leader_id' => $subLeaderB->id,
                'leader_id' => $leaderB->id,
            ],
            [
                'contact_name' => 'Mega Putri',
                'phone' => '628111111004',
                'sub_leader_id' => $subLeaderB->id,
                'leader_id' => $leaderB->id,
            ],
        ];

        foreach ($contacts as $contact) {
            Contact::updateOrCreate([
                'phone' => $contact['phone'],
            ], $contact);
        }

        // Ensure role consistency in case seeders are rerun.
        User::where('id', $superAdmin->id)->update(['role' => User::ROLE_SUPERADMIN, 'leader_id' => null]);
        User::whereIn('id', [$leaderA->id, $leaderB->id])->update(['role' => User::ROLE_LEADER, 'leader_id' => null]);
    }
}
