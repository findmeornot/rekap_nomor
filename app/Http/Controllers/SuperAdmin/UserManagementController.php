<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Services\ContactImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('superadmin.users.index', [
            'leaders' => User::where('role', User::ROLE_LEADER)
                ->withCount('subLeaders')
                ->orderBy('name')
                ->get(),
            'subLeaders' => User::where('role', User::ROLE_SUB_LEADER)
                ->with('leader:id,name')
                ->orderBy('name')
                ->get(),
            'teams' => Schema::hasTable('teams')
                ? Team::withCount(['members', 'leaders', 'subLeaders'])->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function storeTeam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')],
        ]);

        Team::create($validated);

        return back()->with('success', 'Tim berhasil dibuat.');
    }

    public function storeLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'team_id' => ['required', Rule::exists('teams', 'id')],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => User::ROLE_LEADER,
            'team_id' => $validated['team_id'],
        ]);

        return back()->with('success', 'Marketing Utama berhasil dibuat.');
    }

    public function storeSubLeader(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'team_id' => ['required', Rule::exists('teams', 'id')],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => User::ROLE_SUB_LEADER,
            'team_id' => $validated['team_id'],
        ]);

        return back()->with('success', 'Asisten Marketing berhasil dibuat.');
    }

    public function assignTeam(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ]);

        $user->update(['team_id' => $validated['team_id']]);

        return back()->with('success', 'Tim berhasil diubah.');
    }

    public function importForm(): View
    {
        return view('superadmin.import', [
            'teams' => Team::orderBy('name')->get(),
        ]);
    }

    public function import(Request $request, ContactImportService $contactImportService): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', Rule::exists('teams', 'id')],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'leader_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_LEADER))],
            'sub_leader_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_SUB_LEADER))],
        ]);

        $rows = $contactImportService->extractRows($request->file('file'));
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau format kolom tidak dikenali.']);
        }

        $summary = $contactImportService->importRows($rows, [
            'team_id' => $validated['team_id'],
            'input_by' => (int) auth()->id(),
            'sub_leader_id' => $validated['sub_leader_id'] ?? null,
            'leader_id' => $validated['leader_id'] ?? null,
        ]);

        return back()->with(
            'success',
            "Import selesai. Berhasil: {$summary['created']}, Duplikat: {$summary['skipped_duplicate']}, Tidak valid: {$summary['skipped_invalid']}."
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        // Only allow deleting marketing users (leaders or assistants)
        if (!in_array($user->role, [User::ROLE_LEADER, User::ROLE_SUB_LEADER], true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Hanya user marketing yang dapat dihapus.',
                ], 403);
            }

            return back()->withErrors(['user' => 'Hanya user marketing yang dapat dihapus.']);
        }

        // If deleting a marketing utama (leader), detach it from its sub-leaders and contacts
        if ($user->role === User::ROLE_LEADER) {
            User::where('leader_id', $user->id)->update(['leader_id' => null]);
            Contact::where('leader_id', $user->id)->update(['leader_id' => null]);
        }

        // If deleting an asisten marketing, detach it from contacts
        if ($user->role === User::ROLE_SUB_LEADER) {
            Contact::where('sub_leader_id', $user->id)->update(['sub_leader_id' => null]);
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Data user berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'User marketing berhasil dihapus.');
    }
}
