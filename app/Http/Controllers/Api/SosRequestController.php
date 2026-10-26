<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SosRequestResource;
use App\Models\SosRequest;
use App\Services\DisasterOperationsService;
use App\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SosRequestController extends Controller
{
    private const DISASTER_TYPES = [
        'gempa',
        'banjir',
        'longsor',
        'kebakaran',
        'tsunami',
    ];

    private const STATUS_LABELS = [
        'sent' => 'Permintaan SOS tersimpan',
        'assigned' => 'Petugas ditugaskan',
        'en_route' => 'Petugas menuju lokasi',
        'arrived' => 'Petugas tiba di lokasi',
        'completed' => 'Penanganan selesai',
        'rejected' => 'Permintaan tidak dapat ditindaklanjuti',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = SosRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(['histories.actor'])
            ->latest('requested_at')
            ->paginate(20);

        return SosRequestResource::collection($requests);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === UserRole::Masyarakat->value, 403);

        $validated = $request->validate([
            'client_request_id' => ['required', 'uuid'],
            'disaster_type' => ['required', Rule::in(self::DISASTER_TYPES)],
            'description' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        try {
            [$sosRequest, $created] = DB::transaction(function () use (
                $user,
                $validated,
            ): array {
                $now = now();
                $keyId = DB::table('sos_request_idempotency_keys')->insertGetId([
                    'user_id' => $user->id,
                    'client_request_id' => $validated['client_request_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $sosRequest = SosRequest::create([
                    'user_id' => $user->id,
                    'disaster_type' => $validated['disaster_type'],
                    'description' => $validated['description'] ?? null,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'address' => $validated['address'] ?? null,
                    'urgency_level' => 'high',
                    'status' => 'sent',
                    'requested_at' => $now,
                ]);
                DB::table('sos_request_idempotency_keys')
                    ->where('id', $keyId)
                    ->update(['sos_request_id' => $sosRequest->id, 'updated_at' => now()]);

                $sosRequest->histories()->create([
                    'actor_id' => $user->id,
                    'old_status' => null,
                    'new_status' => 'sent',
                    'notes' => 'Permintaan diterima dan disimpan oleh server.',
                ]);

                $user->notifications()->create([
                    'title' => 'Permintaan SOS tersimpan',
                    'message' => sprintf(
                        '%s tersimpan di server dan menunggu tindak lanjut petugas.',
                        $sosRequest->reference(),
                    ),
                    'type' => 'Aktivitas',
                    'related_type' => 'sos_request',
                    'related_id' => $sosRequest->id,
                ]);

                return [$sosRequest, true];
            });
        } catch (QueryException $exception) {
            $sosRequestId = DB::table('sos_request_idempotency_keys')
                ->where('user_id', $user->id)
                ->where('client_request_id', $validated['client_request_id'])
                ->value('sos_request_id');
            $sosRequest = $sosRequestId === null
                ? null
                : SosRequest::query()->find($sosRequestId);

            if ($sosRequest === null) {
                report($exception);

                return response()->json([
                    'message' => 'Server belum dapat menyimpan permintaan SOS. Coba kirim ulang permintaan yang sama beberapa saat lagi.',
                ], 503);
            }

            $created = false;
        }

        $sosRequest->load(['histories.actor']);

        return response()->json([
            'message' => $created
                ? 'Permintaan tersimpan di server dan menunggu tindak lanjut petugas.'
                : 'Permintaan SOS ini sudah tersimpan.',
            'data' => new SosRequestResource($sosRequest),
        ], $created ? 201 : 200);
    }

    public function show(Request $request, int $id): SosRequestResource
    {
        $sosRequest = SosRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(['histories.actor'])
            ->findOrFail($id);

        return new SosRequestResource($sosRequest);
    }

    public function updateStatus(
        Request $request,
        int $id,
        DisasterOperationsService $operations,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['assigned', 'en_route', 'arrived', 'completed', 'rejected'])],
            'assigned_to' => ['required_if:status,assigned', 'nullable', 'integer', 'exists:users,id'],
            'notes' => [
                Rule::requiredIf(in_array($request->input('status'), ['rejected', 'completed'], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $sosRequest = $validated['status'] === 'assigned'
            ? $operations->assignSos(
                $request->user(),
                $id,
                (int) $validated['assigned_to'],
            )
            : $operations->updateSosStatus(
                $request->user(),
                $id,
                $validated['status'],
                $validated['notes'] ?? null,
            );

        return response()->json([
            'message' => $validated['status'] === 'assigned'
                ? 'Petugas berhasil ditugaskan.'
                : 'Status SOS berhasil diperbarui.',
            'data' => new SosRequestResource($sosRequest),
        ]);
    }
}
