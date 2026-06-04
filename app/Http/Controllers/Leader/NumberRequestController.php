<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\NumberRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NumberRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $requests = NumberRequest::with(['requester:id,name', 'recipient:id,name', 'logs.actor:id,name'])
            ->where(fn ($query) => $query->where('requester_id', $user->id)->orWhere('recipient_id', $user->id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $recipients = User::where('role', User::ROLE_MAIN_MARKETING)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        return view('leader.requests.index', [
            'requests' => $requests,
            'recipients' => $recipients,
            'pendingCount' => $requests->where('status', NumberRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:'.User::TARGET_MAIN_MARKETING],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();

        if (Contact::where('main_marketing_id', $user->id)->where('is_contacted', false)->exists()) {
            return $this->errorResponse('Hanya bisa meminta nomor apabila semua nomor tim sendiri sudah ditangani.');
        }

        $recipient = User::where('id', $validated['recipient_id'])
            ->where('role', User::ROLE_MAIN_MARKETING)
            ->first();

        if (! $recipient || $recipient->id === $user->id) {
            return $this->errorResponse('Pilih Marketing Utama lain sebagai sumber nomor.');
        }

        $numberRequest = NumberRequest::create([
            'requester_id' => $user->id,
            'recipient_id' => $recipient->id,
            'amount' => $validated['amount'],
            'message' => $validated['message'] ?? null,
            'status' => NumberRequest::STATUS_PENDING,
        ]);

        $numberRequest->logs()->create([
            'actor_id' => $user->id,
            'status' => NumberRequest::STATUS_PENDING,
            'note' => 'Permintaan dibuat.',
        ]);

        return $this->successResponse('Permintaan nomor berhasil dikirim. Tunggu persetujuan dari Marketing Utama lainnya.');
    }

    public function approve(NumberRequest $numberRequest, Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        abort_unless($numberRequest->recipient_id === $user->id && $numberRequest->isPending(), 403);

        $responseMessage = $request->validate([
            'response_message' => ['nullable', 'string', 'max:500'],
        ])['response_message'] ?? null;

        $available = Contact::where('main_marketing_id', $user->id)
            ->where('is_contacted', false)
            ->count();

        if ($available < $numberRequest->amount) {
            return $this->errorResponse('Tidak cukup nomor tidak terhubung untuk dipindahkan.');
        }

        $transferIds = Contact::where('main_marketing_id', $user->id)
            ->where('is_contacted', false)
            ->orderBy('created_at')
            ->limit($numberRequest->amount)
            ->pluck('id');

        DB::transaction(function () use ($numberRequest, $transferIds, $user, $responseMessage) {
            Contact::whereIn('id', $transferIds)
                ->update([
                    'main_marketing_id' => $numberRequest->requester_id,
                    'assistant_marketing_id' => null,
                ]);

            $numberRequest->update([
                'status' => NumberRequest::STATUS_APPROVED,
                'response_message' => $responseMessage,
                'responded_at' => now(),
            ]);

            $numberRequest->logs()->create([
                'actor_id' => $user->id,
                'status' => NumberRequest::STATUS_APPROVED,
                'note' => 'Permintaan disetujui.',
            ]);
        });

        return $this->successResponse('Permintaan nomor disetujui dan nomor telah dipindahkan.');
    }

    public function reject(NumberRequest $numberRequest, Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        abort_unless($numberRequest->recipient_id === $user->id && $numberRequest->isPending(), 403);

        $responseMessage = $request->validate([
            'response_message' => ['nullable', 'string', 'max:500'],
        ])['response_message'] ?? null;

        $numberRequest->update([
            'status' => NumberRequest::STATUS_REJECTED,
            'response_message' => $responseMessage,
            'responded_at' => now(),
        ]);

        $numberRequest->logs()->create([
            'actor_id' => $user->id,
            'status' => NumberRequest::STATUS_REJECTED,
            'note' => 'Permintaan ditolak.',
        ]);

        return $this->successResponse('Permintaan nomor ditolak.');
    }

    private function successResponse(string $message): RedirectResponse|JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json(['message' => $message], 200);
        }

        return back()->with('success', $message);
    }

    private function errorResponse(string $message): RedirectResponse|JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['request' => $message]);
    }
}
